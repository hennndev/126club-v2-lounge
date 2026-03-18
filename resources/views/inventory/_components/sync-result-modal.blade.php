<div id="syncResultModal"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
    <div class="p-6">
      <div id="syncResultIcon"
           class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4">
      </div>
      <h3 id="syncResultTitle"
          class="text-lg font-bold text-gray-900 text-center mb-1"></h3>
      <p id="syncResultMessage"
         class="text-sm text-gray-500 text-center mb-4"></p>
      <pre id="syncResultOutput"
           class="hidden bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-600 max-h-40 overflow-y-auto whitespace-pre-wrap mb-4"></pre>
      <button onclick="document.getElementById('syncResultModal').classList.add('hidden'); window.location.reload();"
              class="w-full px-4 py-2.5 bg-slate-800 text-white rounded-xl hover:bg-slate-900 font-semibold transition">
        Tutup &amp; Refresh
      </button>
    </div>
  </div>
</div>
