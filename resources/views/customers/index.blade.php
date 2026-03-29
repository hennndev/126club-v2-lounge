<x-app-layout>
  <div class="p-6">
    @if (session('success'))
      <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Customer</h1>
          <p class="text-sm text-gray-500">Premium Management</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    @include('customers._components.tabs')

    <!-- Customers Tab -->
    <div id="customersContent">
      <!-- Search & Add -->
      <div class="mb-4 flex gap-4">
        <div class="flex-1 relative">
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input type="text"
                 id="searchInput"
                 placeholder="Cari customer (nama, telepon, email)..."
                 class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>
        <button onclick="openModal('add')"
                class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition flex items-center gap-2 whitespace-nowrap">
          <svg class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4" />
          </svg>
          Tambah Customer
        </button>
      </div>

      <!-- Table Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-800 text-white">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Kontak</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Visits</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Total Spent</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Last Visit</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200"
                   id="customerTableBody">
              @foreach ($customers as $customer)
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ $customer->user->name }}</div>
                      <div class="text-xs text-gray-500">{{ $customer->customer_code }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm">
                      <div class="flex items-center gap-1 text-gray-900">
                        <svg class="w-4 h-4 text-gray-400"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                          <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $customer->profile->phone ?? '-' }}
                      </div>
                      <div class="flex items-center gap-1 text-gray-500">
                        <svg class="w-4 h-4 text-gray-400"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                          <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $customer->user->email }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="text-lg font-bold text-gray-900">{{ number_format((int) ($customer->transaction_total_visits ?? 0), 0, ',', '.') }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-green-600">Rp {{ number_format((float) ($customer->transaction_lifetime_spending ?? 0), 0, ',', '.') }}</div>
                    <div class="text-xs text-gray-500">{{ number_format(((float) ($customer->transaction_lifetime_spending ?? 0)) / 1000000, 1) }}jt</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ $customer->updated_at->format('d M Y') }}</div>
                    <div class="text-xs text-gray-500">{{ $customer->updated_at->format('H:i') }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <button onclick="editCustomer({{ $customer->id }})"
                            class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition">
                      Edit
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Leaderboard Tab -->
    @include('customers._components.leaderboard')
  </div>

  <!-- Add/Edit Modal -->
  @Include('customers._components.add-edit-modal')

  @push('scripts')
    <script>
      const customers = @json($customers);
      const initialTab = @json(request('tab') === 'leaderboard' ? 'leaderboard' : 'customers');

      function showTab(tab) {
        const customersTab = document.getElementById('customersTab');
        const leaderboardTab = document.getElementById('leaderboardTab');
        const customersContent = document.getElementById('customersContent');
        const leaderboardContent = document.getElementById('leaderboardContent');

        if (tab === 'customers') {
          customersTab.classList.remove('bg-gray-100', 'text-gray-700');
          customersTab.classList.add('bg-slate-800', 'text-white');
          leaderboardTab.classList.remove('bg-slate-800', 'text-white');
          leaderboardTab.classList.add('bg-gray-100', 'text-gray-700');
          customersContent.classList.remove('hidden');
          leaderboardContent.classList.add('hidden');
        } else {
          leaderboardTab.classList.remove('bg-gray-100', 'text-gray-700');
          leaderboardTab.classList.add('bg-slate-800', 'text-white');
          customersTab.classList.remove('bg-slate-800', 'text-white');
          customersTab.classList.add('bg-gray-100', 'text-gray-700');
          leaderboardContent.classList.remove('hidden');
          customersContent.classList.add('hidden');
        }
      }

      function openModal(mode, customerId = null) {
        const modal = document.getElementById('customerModal');
        const form = document.getElementById('customerForm');
        const modalTitle = document.getElementById('modalTitle');
        const formMethod = document.getElementById('formMethod');
        const passwordRequired = document.getElementById('passwordRequired');
        const passwordHint = document.getElementById('passwordHint');
        const passwordInput = document.getElementById('password');
        const customerDataFields = document.getElementById('customerDataFields');

        if (mode === 'add') {
          modalTitle.textContent = 'Tambah Customer';
          form.action = '{{ route('admin.customers.store') }}';
          formMethod.value = 'POST';
          form.reset();
          passwordRequired.style.display = 'inline';
          passwordHint.style.display = 'none';
          passwordInput.required = true;
          customerDataFields.classList.add('hidden');
        } else if (mode === 'edit' && customerId) {
          const customer = customers.find(c => c.id === customerId);
          if (customer) {
            modalTitle.textContent = 'Edit Customer';
            form.action = `/admin/customers/${customerId}`;
            formMethod.value = 'PUT';

            document.getElementById('name').value = customer.user.name;
            document.getElementById('email').value = customer.user.email;
            document.getElementById('phone').value = customer.profile?.phone || '';
            document.getElementById('birth_date').value = customer.profile?.birth_date || '';
            document.getElementById('address').value = customer.profile?.address || '';
            document.getElementById('total_visits').value = customer.total_visits;
            document.getElementById('lifetime_spending').value = customer.lifetime_spending;

            passwordRequired.style.display = 'none';
            passwordHint.style.display = 'block';
            passwordInput.required = false;
            passwordInput.value = '';
            customerDataFields.classList.remove('hidden');
          }
        }

        modal.classList.remove('hidden');
      }

      function closeModal() {
        document.getElementById('customerModal').classList.add('hidden');
      }

      function editCustomer(customerId) {
        openModal('edit', customerId);
      }

      // Search functionality
      document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const tableBody = document.getElementById('customerTableBody');
        const rows = tableBody.getElementsByTagName('tr');

        Array.from(rows).forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });

      // Close modal on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeModal();
        }
      });

      // Close modal on outside click
      document.getElementById('customerModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });

      showTab(initialTab);
    </script>
  @endpush
</x-app-layout>
