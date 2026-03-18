<script>
  const allBookings = @json($bookings);

  function paxEditor(sessionId, initialPax, updateUrl) {
    return {
      editing: false,
      pax: initialPax,
      draft: initialPax ?? '',
      async save() {
        const val = parseInt(this.draft);
        if (!val || val < 1) return;
        const res = await fetch(updateUrl, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            pax: val
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.pax = data.pax;
          this.editing = false;
        }
      },
    };
  }

  function bookingPage(tables, bookedIds, checkedInIds) {
    return {
      selectedCategory: null,
      selectedTableId: null,
      modalOpen: false,
      tables: tables,
      bookedIds: bookedIds,
      checkedInIds: checkedInIds,

      openModal(tableId) {
        this.selectedTableId = tableId;
        const modal = document.getElementById('bookingModal');
        if (modal) {
          modal.classList.remove('hidden');
          // Pre-fill table selection if given
          if (tableId) {
            const table = this.tables.find(t => t.id === tableId);
            if (table) {
              // update table_id hidden input
              const tableInput = document.getElementById('table_id');
              if (tableInput) {
                tableInput.value = tableId;
              }
              // fire custom event to let the modal update its preview
              document.dispatchEvent(new CustomEvent('table-selected', {
                detail: table
              }));
            }
          }
        }
      },
    };
  }

  function closeModal() {
    document.getElementById('bookingModal')?.classList.add('hidden');
  }

  function editBooking(bookingId) {
    const booking = allBookings.find(b => b.id === bookingId);
    if (!booking) return;
    const modal = document.getElementById('bookingModal');
    const form = document.getElementById('bookingForm');
    document.getElementById('modalTitle').textContent = 'Edit Booking';
    form.action = `/admin/bookings/${bookingId}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('table_id').value = booking.table_id;
    document.getElementById('customer_id').value = booking.customer_id;
    document.getElementById('reservation_date').value = booking.reservation_date;
    document.getElementById('reservation_time').value = booking.reservation_time;
    document.getElementById('status').value = booking.status;
    document.getElementById('note').value = booking.note || '';
    modal?.classList.remove('hidden');
  }

  function deleteBooking(bookingId) {
    document.getElementById('deleteForm').action = `/admin/bookings/${bookingId}`;
    document.getElementById('deleteModal')?.classList.remove('hidden');
  }

  function closeDeleteModal() {
    document.getElementById('deleteModal')?.classList.add('hidden');
  }

  function openBookingInfoModal(bookingId) {
    const booking = (window.activeBookingsById || {})[bookingId];
    if (!booking) return;

    const formatReservationDate = (value) => {
      if (!value) {
        return '—';
      }

      const raw = String(value).trim();
      let parsedDate = null;

      if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        parsedDate = new Date(`${raw}T00:00:00`);
      } else {
        parsedDate = new Date(raw);
      }

      if (Number.isNaN(parsedDate.getTime())) {
        return raw;
      }

      return parsedDate.toLocaleDateString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    };

    document.getElementById('biModalBookingName').textContent = booking.booking_name || '—';
    document.getElementById('biModalCustomerName').textContent = booking.customer_name || '—';
    document.getElementById('biModalPhone').textContent = booking.customer_phone || '—';
    document.getElementById('biModalTable').textContent = [booking.table_number, booking.area_name].filter(Boolean).join(' · ') || '—';

    document.getElementById('biModalDate').textContent = formatReservationDate(booking.reservation_date);
    document.getElementById('biModalTime').textContent =
      booking.reservation_time ? booking.reservation_time.substring(0, 5) : '—';

    if (booking.note) {
      document.getElementById('biModalNote').textContent = booking.note;
      document.getElementById('biModalNoteWrap').classList.remove('hidden');
    } else {
      document.getElementById('biModalNoteWrap').classList.add('hidden');
    }

    const statusLabels = {
      confirmed: '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Confirmed</span>',
      checked_in: '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Checked-in</span>',
    };
    document.getElementById('biModalStatusBadge').innerHTML = statusLabels[booking.status] || '';

    const formSection = document.getElementById('biModalStatusForm');
    const readOnlySection = document.getElementById('biModalReadOnlyFooter');
    const biStatusForm = document.getElementById('biStatusForm');

    if (booking.status === 'confirmed') {
      biStatusForm.action = `/admin/bookings/${bookingId}/status`;
      biStatusForm.querySelectorAll('input[name="status"]').forEach(r => {
        r.checked = r.value === booking.status;
      });
      formSection.classList.remove('hidden');
      readOnlySection.classList.add('hidden');
    } else {
      formSection.classList.add('hidden');
      readOnlySection.classList.remove('hidden');
    }

    document.getElementById('bookingInfoModal').classList.remove('hidden');
  }

  function closeBookingInfoModal() {
    document.getElementById('bookingInfoModal')?.classList.add('hidden');
  }

  function openStatusModal(bookingId, currentStatus) {
    const modal = document.getElementById('statusModal');
    const form = document.getElementById('statusForm');
    form.action = `/admin/bookings/${bookingId}/status`;
    const radios = form.querySelectorAll('input[name="status"]');
    radios.forEach(r => {
      r.checked = r.value === currentStatus;
    });
    modal?.classList.remove('hidden');
  }

  function closeStatusModal() {
    document.getElementById('statusModal')?.classList.add('hidden');
  }

  function openAssignWaiterModal(bookingId, currentWaiterId) {
    const modal = document.getElementById('assignWaiterModal');
    const form = document.getElementById('assignWaiterForm');
    form.action = `/admin/bookings/${bookingId}/assign-waiter`;
    const select = document.getElementById('assignWaiterSelect');
    if (select) {
      select.value = currentWaiterId ?? '';
    }
    modal?.classList.remove('hidden');
  }

  function closeAssignWaiterModal() {
    document.getElementById('assignWaiterModal')?.classList.add('hidden');
  }

  @php
    $sessionOrdersJson = $activeSessions->keyBy('session_code')->map(function ($s) {
        return [
            'customer' => $s->reservation?->customer?->profile?->name ?? ($s->reservation?->customer?->customerUser?->name ?? ($s->reservation?->customer?->name ?? 'Tamu')),
            'table' => $s->table?->table_number ?? '—',
            'orders' => $s->orders
                ->map(function ($o) {
                    return [
                        'order_number' => $o->order_number,
                        'ordered_at' => $o->ordered_at?->setTimezone('Asia/Jakarta')->format('H:i'),
                        'status' => $o->status,
                        'total' => (float) $o->total,
                        'items' => $o->items
                            ->map(function ($i) {
                                return [
                                    'item_name' => $i->item_name,
                                    'quantity' => $i->quantity,
                                    'price' => (float) $i->price,
                                    'subtotal' => (float) $i->subtotal,
                                ];
                            })
                            ->values(),
                    ];
                })
                ->values(),
        ];
    });
  @endphp
  const sessionOrdersData = @json($sessionOrdersJson);

  function openOrderHistoryModal(sessionCode) {
    const data = sessionOrdersData[sessionCode];
    if (!data) return;

    document.getElementById('orderHistoryTitle').textContent =
      data.customer + ' — Meja ' + data.table;

    const container = document.getElementById('orderHistoryBody');
    if (data.orders.length === 0) {
      container.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Belum ada order.</p>';
    } else {
      container.innerHTML = data.orders.map(order => {
        const statusClass = {
          pending: 'bg-yellow-100 text-yellow-700',
          processing: 'bg-blue-100 text-blue-700',
          completed: 'bg-green-100 text-green-700',
          cancelled: 'bg-red-100 text-red-700',
        } [order.status] || 'bg-gray-100 text-gray-600';

        const itemRows = order.items.map(item =>
          `<tr class="border-b border-gray-50 last:border-0">
            <td class="py-1.5 pr-3 text-sm text-gray-700">${item.item_name}</td>
            <td class="py-1.5 px-3 text-sm text-gray-500 text-center">${item.quantity}</td>
            <td class="py-1.5 pl-3 text-sm text-gray-500 text-right">Rp ${item.price.toLocaleString('id-ID')}</td>
            <td class="py-1.5 pl-3 text-sm font-medium text-gray-700 text-right">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
          </tr>`
        ).join('');

        return `<div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
          <div class="flex items-center justify-between bg-gray-50 px-4 py-2.5">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-semibold text-gray-600">${order.order_number}</span>
              <span class="text-xs text-gray-400">${order.ordered_at ?? ''}</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">${order.status}</span>
              <span class="text-sm font-bold text-gray-900">Rp ${order.total.toLocaleString('id-ID')}</span>
            </div>
          </div>
          <table class="w-full px-4">
            <thead><tr class="bg-white">
              <th class="px-4 py-1.5 text-left text-xs text-gray-400 font-medium">Item</th>
              <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-medium">Qty</th>
              <th class="px-3 py-1.5 text-right text-xs text-gray-400 font-medium">Harga</th>
              <th class="px-3 py-1.5 text-right text-xs text-gray-400 font-medium">Subtotal</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 px-4">${itemRows}</tbody>
          </table>
        </div>`;
      }).join('');
    }

    document.getElementById('orderHistoryModal')?.classList.remove('hidden');
  }

  function closeOrderHistoryModal() {
    document.getElementById('orderHistoryModal')?.classList.add('hidden');
  }

  // History tab client-side filter
  ['searchInput', 'categoryFilter', 'statusFilter'].forEach(id => {
    document.getElementById(id)?.addEventListener(id === 'searchInput' ? 'input' : 'change', filterHistory);
  });

  function filterHistory() {
    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const category = document.getElementById('categoryFilter')?.value || '';
    const status = document.getElementById('statusFilter')?.value || '';
    document.querySelectorAll('.booking-row').forEach(row => {
      const ms = !search || row.textContent.toLowerCase().includes(search);
      const mc = !category || row.dataset.category == category;
      const mst = !status || row.dataset.status == status;
      row.style.display = ms && mc && mst ? '' : 'none';
    });
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeModal();
      closeDeleteModal();
      closeStatusModal();
      closeBookingInfoModal();
    }
  });

  document.getElementById('bookingModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
  });
  document.getElementById('deleteModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeDeleteModal();
  });
  document.getElementById('statusModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeStatusModal();
  });
  document.getElementById('bookingInfoModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeBookingInfoModal();
  });
</script>
