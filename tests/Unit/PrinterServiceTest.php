<?php

use App\Models\Printer;
use App\Services\PrinterService;

it('uses text-only branding for network printers', function () {
    $service = new class extends PrinterService
    {
        public function shouldUseGraphics(Printer $printer): bool
        {
            return $this->shouldUseGraphicsBranding($printer);
        }
    };

    $networkPrinter = new Printer(['connection_type' => 'network']);
    $filePrinter = new Printer(['connection_type' => 'file']);

    expect($service->shouldUseGraphics($networkPrinter))->toBeFalse()
        ->and($service->shouldUseGraphics($filePrinter))->toBeTrue();
});
