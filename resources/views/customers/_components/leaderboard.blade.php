<div id="leaderboardContent"
     class="hidden">
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
      <h3 class="text-lg font-bold text-gray-900">Top {{ $leaderboardLimit }} Spenders</h3>
      <form method="GET"
            action="{{ route('admin.customers.index') }}"
            class="flex items-center gap-2">
        <input type="hidden"
               name="tab"
               value="leaderboard">
        <label for="leaderboard_limit"
               class="text-sm text-gray-600">Tampilkan</label>
        <select id="leaderboard_limit"
                name="leaderboard_limit"
                onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
          @foreach ($leaderboardLimitOptions as $limitOption)
            <option value="{{ $limitOption }}"
                    @selected($leaderboardLimit === $limitOption)>
              Top {{ $limitOption }}
            </option>
          @endforeach
        </select>
      </form>
    </div>
    <div class="space-y-4">
      @foreach ($leaderboard as $index => $customer)
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white font-bold">
              #{{ $index + 1 }}
            </div>
          </div>
          <div class="flex-1">
            <div class="font-medium text-gray-900">{{ $customer->user->name }}</div>
            <div class="text-xs text-gray-500">{{ $customer->customer_code }}</div>
          </div>
          <div class="text-right">
            <div class="font-bold text-green-600">Rp {{ number_format(((float) ($customer->transaction_lifetime_spending ?? 0)) / 1000000, 1) }}jt</div>
          </div>
          <span class="px-3 py-1 text-xs font-medium rounded-full {{ $customer->membership_tier === 'Untouchable' ? 'bg-yellow-100 text-yellow-700' : 'bg-purple-100 text-purple-700' }}">
            {{ $customer->membership_tier }}
          </span>
        </div>
      @endforeach
    </div>
  </div>
</div>
