/**
 * Walk-in Checkout Alpine.js component factory.
 *
 * Registered separately from posApp to prevent state pollution
 * between the walk-in customer selection flow and the booking checkout flow.
 *
 * Usage in the POS blade (registered via a separate <script> block):
 *   Alpine.data('walkInCheckout', walkInCheckoutFactory);
 *
 * Communication with posApp:
 *   - Dispatches 'walk-in-proceed' event (bubbles to posApp root) when customer is confirmed.
 *   - Dispatches 'pos-toast' event (bubbles to window) so posApp can show the toast notification.
 *   - Listens for 'walk-in-reset' on window to reset its own state (triggered by posApp when
 *     the user navigates back to the walk-in step).
 *
 * Note: `posRoutes` is a global variable injected by the POS blade; it is only
 * referenced inside methods which are only called when the component is active.
 */
export function walkInCheckoutFactory() {
    return {
        walkInSearch: '',
        walkInFoundCustomers: [],
        walkInSearching: false,
        walkInSelected: null,
        walkInNewName: '',
        walkInNewPhone: '',
        walkInCreating: false,
        walkInCreateMode: false,
        walkInSelectedTable: null,

        /** Reset all state — called when the user re-enters the walk-in step. */
        reset() {
            this.walkInSearch = '';
            this.walkInFoundCustomers = [];
            this.walkInSearching = false;
            this.walkInSelected = null;
            this.walkInNewName = '';
            this.walkInNewPhone = '';
            this.walkInCreating = false;
            this.walkInCreateMode = false;
            this.walkInSelectedTable = null;
        },

        async searchWalkInCustomers() {
            if (this.walkInSearch.length < 2) {
                this.walkInFoundCustomers = [];
                return;
            }
            this.walkInSearching = true;
            try {
                const res = await fetch(
                    posRoutes.walkInSearchCustomers + '?q=' + encodeURIComponent(this.walkInSearch),
                    { headers: { Accept: 'application/json' } },
                );
                const data = await res.json();
                this.walkInFoundCustomers = data.customers ?? [];
            } catch (e) {
                this.walkInFoundCustomers = [];
            } finally {
                this.walkInSearching = false;
            }
        },

        selectWalkInCustomer(c) {
            this.walkInSelected = c;
            this.walkInFoundCustomers = [];
            this.walkInSearch = '';
        },

        async createWalkInCustomer() {
            if (!this.walkInNewName.trim() || this.walkInCreating) {
                return;
            }
            this.walkInCreating = true;
            try {
                const res = await fetch(posRoutes.walkInCreateCustomer, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.walkInNewName,
                        phone: this.walkInNewPhone,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.walkInSelected = data.customer;
                    this.walkInCreateMode = false;
                    this.walkInNewName = '';
                    this.walkInNewPhone = '';
                } else {
                    this.toast(data.message || 'Gagal membuat customer', 'error');
                }
            } catch (e) {
                this.toast('Terjadi kesalahan. Coba lagi.', 'error');
            } finally {
                this.walkInCreating = false;
            }
        },

        selectWalkInTable(t) {
            this.walkInSelectedTable = t;
        },

        /** Confirm customer selection and hand off to posApp via event. */
        proceedToCheckout() {
            if (!this.walkInSelected) {
                return;
            }
            this.$dispatch('walk-in-proceed', {
                id: this.walkInSelected.id,
                name: this.walkInSelected.name,
                phone: this.walkInSelected.phone || '',
            });
        },

        /** Proxy toast so errors surface in posApp's toast UI. */
        toast(message, type = 'success') {
            this.$dispatch('pos-toast', { message, type });
        },
    };
}
