<?php

namespace App\Services;

use App\Models\BarOrder;
use App\Models\GeneralSetting;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer as EscposPrinter;

class PrinterService
{
    /**
     * Create the appropriate print connector based on printer model.
     */
    protected function createConnector(Printer $printer): NetworkPrintConnector|FilePrintConnector|WindowsPrintConnector
    {
        return match ($printer->connection_type) {
            'network' => new NetworkPrintConnector(
                $printer->ip,
                (int) $printer->port,
                (int) $printer->timeout
            ),
            'file' => new FilePrintConnector($printer->path),
            'windows' => new WindowsPrintConnector($printer->path),
            default => throw new \InvalidArgumentException("Unknown printer connection type: {$printer->connection_type}"),
        };
    }

    /**
     * Write a human-readable print simulation to storage/logs/printer.log.
     */
    protected function logPrint(string $title, array $lines): void
    {
        $separator = str_repeat('-', 42);
        $content = implode("\n", [
            '',
            $separator,
            '  [PRINT SIMULATION] '.$title,
            '  '.now()->format('d/m/Y H:i:s'),
            $separator,
            ...array_map(fn ($l) => '  '.$l, $lines),
            $separator,
        ]);

        file_put_contents(
            storage_path('logs/printer.log'),
            $content."\n",
            FILE_APPEND
        );
    }

    /**
     * Print a receipt for the given order.
     */
    public function printReceipt(Order $order, Printer $printer): bool
    {
        $walkInTotals = $this->calculateWalkInReceiptTotals($order);

        if ($printer->connection_type === 'log') {
            $lines = [
                "Order  : {$order->order_number}",
                'Date   : '.$order->ordered_at->format('d/m/Y H:i'),
                'Table  : '.($order->tableSession?->table?->table_number ?? 'N/A'),
                '',
            ];
            foreach ($order->items as $item) {
                $lines[] = "  {$item->quantity}x {$item->item_name}  Rp ".number_format($item->subtotal, 0, ',', '.');
            }
            $lines[] = '';
            if ($walkInTotals !== null) {
                $lines[] = 'Subtotal: Rp '.number_format($walkInTotals['items_total'], 0, ',', '.');

                if ($walkInTotals['discount_amount'] > 0) {
                    $lines[] = 'Diskon  : Rp '.number_format($walkInTotals['discount_amount'], 0, ',', '.');
                }

                if ($walkInTotals['service_charge'] > 0) {
                    $lines[] = 'Service : Rp '.number_format($walkInTotals['service_charge'], 0, ',', '.');
                }

                if ($walkInTotals['tax'] > 0) {
                    $lines[] = 'PPN     : Rp '.number_format($walkInTotals['tax'], 0, ',', '.');
                }
            }
            $lines[] = 'TOTAL  : Rp '.number_format($order->total, 0, ',', '.');
            $this->logPrint('RECEIPT', $lines);

            return true;
        }

        $connector = $this->createConnector($printer);
        $escpos = new EscposPrinter($connector);

        try {
            // Logo (if configured)
            $this->printLogo($escpos, $printer);

            // Header
            $this->printHeader($escpos, $printer);

            // Order info
            $escpos->setEmphasis(true);
            $escpos->text("Order: {$order->order_number}\n");
            $escpos->setEmphasis(false);
            $escpos->text("Date: {$order->ordered_at->format('d/m/Y H:i')}\n");
            $tableName = $order->tableSession?->table?->table_number ?? 'N/A';
            $escpos->text("Table: {$tableName}\n");
            $escpos->text(str_repeat('-', $printer->width)."\n");

            // Items
            $escpos->setEmphasis(true);
            $escpos->text($this->padLine('Item', 'Qty', 'Price', 'Subtotal', $printer->width));
            $escpos->setEmphasis(false);
            $escpos->text(str_repeat('-', $printer->width)."\n");

            foreach ($order->items as $item) {
                $escpos->text($this->formatItemLine($item, $printer->width));
            }

            $escpos->text(str_repeat('-', $printer->width)."\n");

            // Totals
            if ($walkInTotals !== null) {
                $escpos->text($this->padLine('Subtotal', '', '', 'Rp '.number_format($walkInTotals['items_total'], 0, ',', '.'), $printer->width));

                if ($walkInTotals['discount_amount'] > 0) {
                    $escpos->text($this->padLine('Diskon', '', '', 'Rp '.number_format($walkInTotals['discount_amount'], 0, ',', '.'), $printer->width));
                }

                if ($walkInTotals['service_charge'] > 0) {
                    $escpos->text($this->padLine('Service', '', '', 'Rp '.number_format($walkInTotals['service_charge'], 0, ',', '.'), $printer->width));
                }

                if ($walkInTotals['tax'] > 0) {
                    $escpos->text($this->padLine('PPN', '', '', 'Rp '.number_format($walkInTotals['tax'], 0, ',', '.'), $printer->width));
                }

                $escpos->text(str_repeat('-', $printer->width)."\n");
            }

            $escpos->setEmphasis(true);
            $escpos->text($this->padLine('TOTAL', '', '', 'Rp '.number_format($order->total, 0, ',', '.'), $printer->width));
            $escpos->setEmphasis(false);

            // QR Code (optional)
            if ($printer->show_qr_code) {
                $escpos->feed(2);
                $escpos->qrCode($order->order_number, EscposPrinter::QR_ECLEVEL_M, 4);
            }

            // Footer
            $this->printFooter($escpos, $printer);

            // Cut paper
            $escpos->feed(3);
            $escpos->cut();

            return true;
        } finally {
            $escpos->close();
        }
    }

    /**
     * Print a test receipt to verify printer connection.
     */
    public function testPrint(Printer $printer): bool
    {
        if ($printer->connection_type === 'log') {
            $this->logPrint('TEST PRINT', [
                "Printer    : {$printer->name}",
                'Connection : log (simulation)',
                'Status     : OK — printer simulation working!',
            ]);

            return true;
        }

        $connector = $this->createConnector($printer);
        $escpos = new EscposPrinter($connector);

        try {
            // Logo (if configured)
            $this->printLogo($escpos, $printer);

            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->text("=== PRINTER TEST ===\n");
            $escpos->text("Printer: {$printer->name}\n");
            $escpos->text("Connection: {$printer->connection_type}\n");
            $escpos->text('Time: '.now()->format('d/m/Y H:i:s')."\n");
            $escpos->text(str_repeat('-', $printer->width)."\n");
            $escpos->text("Printer is working correctly!\n");
            $escpos->setJustification(EscposPrinter::JUSTIFY_LEFT);
            $escpos->feed(3);
            $escpos->cut();

            return true;
        } finally {
            $escpos->close();
        }
    }

    /**
     * Print logo if configured.
     */
    protected function printLogo(EscposPrinter $escpos, Printer $printer): void
    {
        if (! $printer->logo_path) {
            return;
        }

        $logoPath = storage_path('app/public/'.$printer->logo_path);

        if (! file_exists($logoPath)) {
            return;
        }

        try {
            $img = EscposImage::load($logoPath);
            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->graphics($img);
            $escpos->feed(1);
        } catch (\Exception $e) {
            // Silently fail if image cannot be loaded
        }
    }

    /**
     * Print the receipt header.
     */
    protected function printHeader(EscposPrinter $escpos, Printer $printer): void
    {
        $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
        $escpos->setEmphasis(true);
        $escpos->setTextSize(2, 2);
        $escpos->text($printer->header."\n");
        $escpos->setTextSize(1, 1);
        $escpos->setEmphasis(false);
        $escpos->setJustification(EscposPrinter::JUSTIFY_LEFT);
        $escpos->feed(1);
    }

    /**
     * Print the receipt footer.
     */
    protected function printFooter(EscposPrinter $escpos, Printer $printer): void
    {
        $escpos->feed(2);
        $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
        $escpos->text($printer->footer."\n");
        $escpos->setJustification(EscposPrinter::JUSTIFY_LEFT);
    }

    /**
     * Format an order item line for printing.
     */
    protected function formatItemLine($item, int $width): string
    {
        $name = $this->truncate($item->item_name, 12);
        $qty = (string) $item->quantity;
        $price = number_format($item->price, 0, ',', '.');
        $subtotal = number_format($item->subtotal, 0, ',', '.');

        return $this->padLine($name, $qty, $price, $subtotal, $width)."\n";
    }

    /**
     * Pad a line with columns for receipt printing.
     */
    protected function padLine(string $col1, string $col2, string $col3, string $col4, int $width): string
    {
        $col1Width = 14;
        $col2Width = 4;
        $col3Width = 10;
        $col4Width = $width - $col1Width - $col2Width - $col3Width;

        return str_pad($this->truncate($col1, $col1Width), $col1Width)
            .str_pad($col2, $col2Width, ' ', STR_PAD_LEFT)
            .str_pad($col3, $col3Width, ' ', STR_PAD_LEFT)
            .str_pad($col4, $col4Width, ' ', STR_PAD_LEFT);
    }

    /**
     * Truncate a string to the specified length.
     */
    protected function truncate(string $str, int $length): string
    {
        if (strlen($str) <= $length) {
            return $str;
        }

        return substr($str, 0, $length - 1).'.';
    }

    /**
     * @return array<string, float>|null
     */
    protected function calculateWalkInReceiptTotals(Order $order): ?array
    {
        if ($order->table_session_id !== null) {
            return null;
        }

        $generalSettings = GeneralSetting::instance();
        $itemsTotal = (float) $order->items_total;
        $discountAmount = (float) $order->discount_amount;
        $subtotalAfterDiscount = max($itemsTotal - $discountAmount, 0);
        $serviceCharge = round($subtotalAfterDiscount * (((float) $generalSettings->service_charge_percentage) / 100));
        $tax = round(($subtotalAfterDiscount + $serviceCharge) * (((float) $generalSettings->tax_percentage) / 100));

        return [
            'items_total' => $itemsTotal,
            'discount_amount' => $discountAmount,
            'service_charge' => $serviceCharge,
            'tax' => $tax,
        ];
    }

    /**
     * Print a kitchen order ticket.
     */
    public function printKitchenTicket(KitchenOrder $kitchenOrder, Printer $printer): bool
    {
        if ($printer->connection_type === 'log') {
            $lines = [
                "Order : #{$kitchenOrder->order_number}",
                'Table : '.($kitchenOrder->table?->table_number ?? 'N/A'),
                'Time  : '.now()->format('H:i'),
                '',
            ];
            foreach ($kitchenOrder->items as $item) {
                $name = $item->recipe->inventoryItem->name ?? 'Item';
                $lines[] = "  {$item->quantity}x {$name}";
            }
            $this->logPrint('KITCHEN ORDER', $lines);

            return true;
        }

        $connector = $this->createConnector($printer);
        $escpos = new EscposPrinter($connector);

        try {
            // Header
            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->setEmphasis(true);
            $escpos->setTextSize(2, 2);
            $escpos->text("KITCHEN\n");
            $escpos->setTextSize(1, 1);
            $escpos->text("Order #{$kitchenOrder->order_number}\n");
            $escpos->setEmphasis(false);
            $escpos->setJustification(EscposPrinter::JUSTIFY_LEFT);
            $escpos->feed(1);

            // Order info
            $tableName = $kitchenOrder->table?->table_number ?? 'N/A';
            $escpos->text("Table: {$tableName}\n");
            $escpos->text('Time: '.now()->format('H:i')."\n");
            $escpos->text(str_repeat('-', $printer->width)."\n");

            // Items
            $escpos->setEmphasis(true);
            $escpos->text("ITEM\n");
            $escpos->setEmphasis(false);

            foreach ($kitchenOrder->items as $item) {
                $name = $item->recipe->inventoryItem->name ?? $item->recipe->type ?? 'Item';
                $escpos->setEmphasis(true);
                $escpos->text("  {$item->quantity}x {$name}\n");
                $escpos->setEmphasis(false);
            }

            $escpos->text(str_repeat('-', $printer->width)."\n");
            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->text("*** KITCHEN ORDER ***\n");

            // Cut paper
            $escpos->feed(3);
            $escpos->cut();

            return true;
        } finally {
            $escpos->close();
        }
    }

    /**
     * Print a bar order ticket.
     */
    public function printBarTicket(BarOrder $barOrder, Printer $printer): bool
    {
        if ($printer->connection_type === 'log') {
            $lines = [
                "Order : #{$barOrder->order_number}",
                'Table : '.($barOrder->table?->table_number ?? 'N/A'),
                'Time  : '.now()->format('H:i'),
                '',
            ];
            foreach ($barOrder->items as $item) {
                $name = $item->recipe?->inventoryItem?->name ?? $item->inventoryItem?->name ?? 'Item';
                $lines[] = "  {$item->quantity}x {$name}";
            }
            $this->logPrint('BAR ORDER', $lines);

            return true;
        }

        $connector = $this->createConnector($printer);
        $escpos = new EscposPrinter($connector);

        try {
            // Header
            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->setEmphasis(true);
            $escpos->setTextSize(2, 2);
            $escpos->text("BAR\n");
            $escpos->setTextSize(1, 1);
            $escpos->text("Order #{$barOrder->order_number}\n");
            $escpos->setEmphasis(false);
            $escpos->setJustification(EscposPrinter::JUSTIFY_LEFT);
            $escpos->feed(1);

            // Order info
            $tableName = $barOrder->table?->table_number ?? 'N/A';
            $escpos->text("Table: {$tableName}\n");
            $escpos->text('Time: '.now()->format('H:i')."\n");
            $escpos->text(str_repeat('-', $printer->width)."\n");

            // Items
            $escpos->setEmphasis(true);
            $escpos->text("ITEM\n");
            $escpos->setEmphasis(false);

            foreach ($barOrder->items as $item) {
                $name = $item->recipe?->inventoryItem?->name ?? $item->inventoryItem?->name ?? 'Item';
                $escpos->setEmphasis(true);
                $escpos->text("  {$item->quantity}x {$name}\n");
                $escpos->setEmphasis(false);
            }

            $escpos->text(str_repeat('-', $printer->width)."\n");
            $escpos->setJustification(EscposPrinter::JUSTIFY_CENTER);
            $escpos->text("*** BAR ORDER ***\n");

            // Cut paper
            $escpos->feed(3);
            $escpos->cut();

            return true;
        } finally {
            $escpos->close();
        }
    }
}
