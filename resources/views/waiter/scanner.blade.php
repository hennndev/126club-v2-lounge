<x-waiter-mobile-layout>
  <div class="p-5"
       x-data="waiterScanner()"
       x-init="init()">

    <!-- Header -->
    <div class="mb-5">
      <h1 class="text-2xl font-bold">Scanner</h1>
      <p class="text-slate-700 text-sm mt-0.5">Confirm customer bookings</p>
    </div>

    <!-- Tab Switcher -->
    <div class="flex bg-slate-200 rounded-full p-1 mb-5">
      <button @click="tab = 'qr'"
              :class="tab === 'qr' ? 'bg-white text-gray-900 shadow-sm' : 'text-slate-700'"
              class="flex-1 py-2.5 rounded-full flex items-center justify-center gap-2 font-semibold text-sm transition-all duration-200">
        <svg class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
        </svg>
        QR Scan
      </button>
      <button @click="tab = 'manual'; stopCamera()"
              :class="tab === 'manual' ? 'bg-white text-gray-900 shadow-sm' : 'text-slate-700'"
              class="flex-1 py-2.5 rounded-full flex items-center justify-center gap-2 font-semibold text-sm transition-all duration-200">
        <svg class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Manual Input
      </button>
    </div>

    <!-- QR Scan Tab -->
    <div x-show="tab === 'qr'"
         x-transition.opacity>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <!-- Camera Preview or Placeholder -->
        <div class="relative mb-5 h-[420px] sm:h-[460px] rounded-xl overflow-hidden bg-black transition-all duration-200">
          <!-- Placeholder (camera off) -->
          <div x-show="!cameraActive"
               class="absolute inset-0 flex flex-col items-center justify-center bg-slate-100">
            <svg class="w-20 h-20 mb-3 text-slate-500"
                 fill="currentColor"
                 viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                    d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z"
                    clip-rule="evenodd" />
              <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
            </svg>
            <p class="text-sm font-medium text-slate-600">Kamera Tidak Aktif</p>
          </div>

          <!-- html5-qrcode renders into this element — keep it clean, no children -->
          <div id="qrCameraContainer"
               x-show="cameraActive"
               class="w-full h-full"></div>

          <!-- Overlay drawn on top of the camera feed (sibling, not child of #qrCameraContainer) -->
          <div x-show="cameraActive"
               class="absolute inset-0">
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-[85%] min-w-[240px] max-w-[300px] aspect-square border-4 border-teal-400 rounded-2xl opacity-90 shadow-lg pointer-events-none"></div>
            </div>

          </div>
        </div>

        <h2 class="text-lg font-bold text-center mb-1 text-slate-900">Scan QR Code</h2>
        <p class="text-slate-700 text-sm text-center mb-5">Point camera at booking QR code</p>

        <button x-show="!cameraActive"
                @click="startCamera()"
                class="w-full bg-teal-500 text-white py-4 rounded-full font-bold flex items-center justify-center gap-2 mb-3">
          <svg class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Start Scan
        </button>
        <button x-show="cameraActive"
                @click="stopCamera()"
                class="w-full bg-red-600 text-white py-4 rounded-full font-bold mb-3">
          Stop Scan
        </button>

        <button x-show="cameraActive && torchSupported"
                @click="toggleTorch()"
                :disabled="torchBusy"
                class="w-full bg-amber-500 text-white py-3 rounded-full font-semibold mb-3 disabled:opacity-60 disabled:cursor-not-allowed">
          <span x-text="torchBusy ? 'Memproses...' : (torchOn ? 'Flash Off' : 'Flash On')"></span>
        </button>

        <!-- Manual code entry (quick entry below Start Scan) -->
        <input type="text"
               x-model="manualCode"
               @keydown.enter="processCode(manualCode)"
               placeholder="Atau ketik kode QR..."
               class="w-full bg-slate-100 text-slate-900 border border-slate-200 rounded-full px-5 py-3.5 text-sm placeholder-slate-400 focus:outline-none focus:border-teal-400" />
      </div>

      <!-- Scan Result -->
      <div x-show="scanResult"
           x-transition
           style="display: none;"
           class="mt-4 bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
        <!-- Success result -->
        <template x-if="scanResult && scanResult.success">
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h3 class="font-bold text-green-600">Booking Ditemukan</h3>
            </div>
            <div class="space-y-2 text-sm mb-4">
              <div class="flex justify-between">
                <span class="text-slate-700">Meja</span>
                <span class="font-semibold text-slate-900"
                      x-text="scanResult.data?.table?.table_number ?? '—'"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-700">Customer</span>
                <span class="font-semibold text-slate-900"
                      x-text="scanResult.data?.reservation?.customer?.name ?? '—'"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-700">Status</span>
                <span class="font-semibold capitalize text-slate-900"
                      x-text="scanResult.data?.reservation?.status ?? '—'"></span>
              </div>
            </div>
            <button x-show="scanResult.data?.reservation?.status === 'confirmed'"
                    @click="processCheckIn()"
                    :disabled="processingCheckIn"
                    class="w-full bg-teal-500 text-white py-3.5 rounded-full font-bold disabled:opacity-50">
              <span x-text="processingCheckIn ? 'Memproses...' : 'Proses Check-in'"></span>
            </button>
            <div x-show="scanResult.data?.reservation?.status === 'checked_in'"
                 class="w-full bg-blue-50 text-blue-600 py-3 rounded-full font-medium text-center text-sm border border-blue-200">
              Sudah Check-in
            </div>
          </div>
        </template>
        <!-- Error result -->
        <template x-if="scanResult && !scanResult.success">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-white"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <p class="text-red-400 text-sm"
               x-text="scanResult.message"></p>
          </div>
        </template>
      </div>

      <!-- Check-in success notification -->
      <div x-show="checkInSuccess"
           x-transition
           style="display: none;"
           class="mt-4 bg-green-50 border border-green-200 rounded-2xl p-5 text-center">
        <div class="text-3xl mb-2">✅</div>
        <p class="font-bold text-green-700">Check-in Berhasil!</p>
        <p class="text-green-600 text-sm mt-1"
           x-text="checkInSuccessMsg"></p>
        <div x-show="waiterAssigned"
             class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-xs font-medium">
          <svg class="w-3.5 h-3.5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Kamu di-assign sebagai waiter
        </div>
      </div>

      <!-- How to Use -->
      <div class="mt-4 bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
        <h3 class="font-semibold text-slate-900 flex items-center gap-2 mb-3">
          <svg class="w-4 h-4 text-slate-600"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          How to use
        </h3>
        <ol class="space-y-2 text-sm text-slate-700">
          <li>1. Tap "Start Scan" button</li>
          <li>2. Allow camera access when prompted</li>
          <li>3. Point camera at booking QR code</li>
          <li>4. Wait for automatic detection</li>
        </ol>
      </div>
    </div>

    <!-- Manual Input Tab -->
    <div x-show="tab === 'manual'"
         x-transition.opacity
         style="display: none;">
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h2 class="font-bold mb-4 text-slate-900">Cari Booking Manual</h2>
        <div class="space-y-3">
          <input type="text"
                 x-model="manualSearch"
                 @input.debounce.400ms="searchBooking()"
                 placeholder="Cari nama customer atau kode booking..."
                 class="w-full bg-slate-100 text-slate-900 border border-slate-200 rounded-xl px-4 py-3.5 text-sm placeholder-slate-400 focus:outline-none focus:border-teal-400" />
        </div>

        <!-- Search results -->
        <div class="mt-4 space-y-3">
          <template x-for="booking in searchResults"
                    :key="booking.id">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <p class="font-semibold text-slate-900"
                     x-text="booking.customer?.name ?? '—'"></p>
                  <p class="text-slate-700 text-xs mt-0.5"
                     x-text="'Meja ' + (booking.table?.table_number ?? '?')"></p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="booking.status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'"
                      x-text="booking.status"></span>
              </div>
              <button x-show="booking.status === 'confirmed'"
                      @click="processCheckInById(booking.id)"
                      :disabled="processingCheckIn"
                      class="w-full mt-2 bg-teal-500 text-white py-2.5 rounded-lg font-semibold text-sm disabled:opacity-50">
                Proses Check-in
              </button>
            </div>
          </template>
          <p x-show="manualSearch.length > 2 && searchResults.length === 0"
             class="text-slate-600 text-sm text-center py-4">
            Tidak ada booking ditemukan.
          </p>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
      function waiterScanner() {
        return {
          tab: 'qr',
          cameraActive: false,
          manualCode: '',
          manualSearch: '',
          searchResults: [],
          scanResult: null,
          checkInSuccess: false,
          checkInSuccessMsg: '',
          processingCheckIn: false,
          waiterAssigned: false,
          qrScanner: null,
          lastScannedCode: null,
          torchSupported: false,
          torchOn: false,
          torchBusy: false,

          init() {},

          async safeClearScanner() {
            if (!this.qrScanner) {
              return;
            }

            try {
              await this.qrScanner.stop();
            } catch (_) {}

            try {
              await this.qrScanner.clear();
            } catch (_) {}

            this.qrScanner = null;
          },

          resetTorchState() {
            this.torchSupported = false;
            this.torchOn = false;
            this.torchBusy = false;
          },

          async setupTorchSupport() {
            this.resetTorchState();

            const container = document.getElementById('qrCameraContainer');
            const videoElement = container?.querySelector('video');
            const mediaStream = videoElement?.srcObject instanceof MediaStream ? videoElement.srcObject : null;
            const mediaTrack = mediaStream?.getVideoTracks?.()[0] ?? null;

            if (!mediaTrack || typeof mediaTrack.getCapabilities !== 'function') {
              return;
            }

            const capabilities = mediaTrack.getCapabilities();
            this.torchSupported = Boolean(capabilities?.torch);
          },

          async toggleTorch() {
            if (!this.cameraActive || !this.torchSupported || this.torchBusy) {
              return;
            }

            const container = document.getElementById('qrCameraContainer');
            const videoElement = container?.querySelector('video');
            const mediaStream = videoElement?.srcObject instanceof MediaStream ? videoElement.srcObject : null;
            const mediaTrack = mediaStream?.getVideoTracks?.()[0] ?? null;

            if (!mediaTrack || typeof mediaTrack.applyConstraints !== 'function') {
              this.resetTorchState();

              return;
            }

            this.torchBusy = true;

            try {
              const nextTorchState = !this.torchOn;
              await mediaTrack.applyConstraints({
                advanced: [{
                  torch: nextTorchState
                }]
              });
              this.torchOn = nextTorchState;
            } catch (_) {
              this.torchOn = false;
            } finally {
              this.torchBusy = false;
            }
          },

          async startCamera() {
            this.scanResult = null;
            this.checkInSuccess = false;

            if (this.cameraActive) {
              return;
            }

            if (typeof Html5Qrcode === 'undefined') {
              alert('Library scanner tidak termuat. Coba refresh halaman.');
              return;
            }

            try {
              this.cameraActive = true;
              await new Promise((resolve) => this.$nextTick(resolve));

              this.qrScanner = new Html5Qrcode('qrCameraContainer');

              const scanConfig = {
                fps: 10,
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                  const smallestEdge = Math.min(viewfinderWidth, viewfinderHeight);
                  const dynamicEdge = Math.floor(smallestEdge * 0.8);
                  const boxEdge = Math.max(240, Math.min(dynamicEdge, 300));

                  return {
                    width: boxEdge,
                    height: boxEdge
                  };
                }
              };

              const onDecoded = (decodedText) => {
                if (decodedText !== this.lastScannedCode) {
                  this.lastScannedCode = decodedText;
                  this.processCode(decodedText);
                  this.stopCamera();
                }
              };

              try {
                await this.qrScanner.start({
                  facingMode: 'environment'
                }, scanConfig, onDecoded, () => {});
              } catch (_) {
                const cameras = await Html5Qrcode.getCameras();

                if (!cameras || cameras.length === 0) {
                  throw new Error('Kamera tidak ditemukan pada perangkat ini.');
                }

                const backCamera = cameras.find((camera) => /(back|rear|environment)/i.test(camera.label || ''));
                const selectedCamera = backCamera ?? cameras[0];

                await this.qrScanner.start({
                  deviceId: {
                    exact: selectedCamera.id
                  }
                }, scanConfig, onDecoded, () => {});
              }

              await new Promise((resolve) => setTimeout(resolve, 150));
              await this.setupTorchSupport();

              this.lastScannedCode = null;
            } catch (e) {
              await this.safeClearScanner();
              this.cameraActive = false;
              this.resetTorchState();

              const reason = e?.message ? `\nDetail: ${e.message}` : '';
              alert('Tidak bisa mengakses kamera. Pastikan izin kamera diberikan dan tidak dipakai aplikasi lain.' + reason);
            }
          },

          async stopCamera() {
            if (this.torchOn) {
              try {
                const container = document.getElementById('qrCameraContainer');
                const videoElement = container?.querySelector('video');
                const mediaStream = videoElement?.srcObject instanceof MediaStream ? videoElement.srcObject : null;
                const mediaTrack = mediaStream?.getVideoTracks?.()[0] ?? null;

                if (mediaTrack && typeof mediaTrack.applyConstraints === 'function') {
                  await mediaTrack.applyConstraints({
                    advanced: [{
                      torch: false
                    }]
                  });
                }
              } catch (_) {}
            }

            await this.safeClearScanner();
            this.cameraActive = false;
            this.resetTorchState();
          },

          normalizeScannedCode(rawCode) {
            const scannedValue = String(rawCode ?? '').trim();

            if (!scannedValue) {
              return '';
            }

            try {
              const parsedJson = JSON.parse(scannedValue);

              if (typeof parsedJson === 'string' && parsedJson.trim() !== '') {
                return parsedJson.trim();
              }

              if (typeof parsedJson === 'object' && parsedJson !== null) {
                const qrCodeFromObject = parsedJson.qr_code ?? parsedJson.qrCode ?? parsedJson.code;

                if (typeof qrCodeFromObject === 'string' && qrCodeFromObject.trim() !== '') {
                  return qrCodeFromObject.trim();
                }
              }
            } catch (_) {}

            try {
              const parsedUrl = new URL(scannedValue);
              const qrCodeFromUrl = parsedUrl.searchParams.get('qr_code') ??
                parsedUrl.searchParams.get('code') ??
                parsedUrl.searchParams.get('check_in_qr_code');

              if (qrCodeFromUrl && qrCodeFromUrl.trim() !== '') {
                return qrCodeFromUrl.trim();
              }
            } catch (_) {}

            return scannedValue;
          },

          async processCode(code) {
            if (!code) {
              return;
            }

            const normalizedCode = this.normalizeScannedCode(code);

            if (!normalizedCode) {
              this.scanResult = {
                success: false,
                message: 'QR code tidak terbaca.'
              };
              return;
            }

            this.scanResult = null;
            this.checkInSuccess = false;

            try {
              const res = await fetch('{{ route('waiter.table-scanner.scan') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                  qr_code: normalizedCode
                }),
              });

              this.scanResult = await res.json();

              if (!this.scanResult.success && res.status !== 404) {
                // also try as check-in QR
                await this.processCheckInQr(normalizedCode);
              }
            } catch (e) {
              this.scanResult = {
                success: false,
                message: 'Gagal memproses QR code.'
              };
            }
          },

          async processCheckInQr(code) {
            try {
              const res = await fetch('{{ route('waiter.table-scanner.process-checkin') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                  qr_code: code
                }),
              });
              const data = await res.json();
              if (data.success) {
                this.scanResult = null;
                this.checkInSuccess = true;
                this.waiterAssigned = data.data?.waiter_assigned ?? false;
                this.checkInSuccessMsg = `${data.data?.customer ?? ''} - Meja ${data.data?.table ?? ''}`;
                setTimeout(() => {
                  this.checkInSuccess = false;
                  this.waiterAssigned = false;
                }, 5000);
              } else {
                this.scanResult = {
                  success: false,
                  message: data.message ?? 'QR code check-in tidak valid.'
                };
              }
            } catch (_) {
              this.scanResult = {
                success: false,
                message: 'Gagal memproses check-in QR.'
              };
            }
          },

          async processCheckIn() {
            if (!this.scanResult?.data?.table?.qr_code) {
              return;
            }
            this.processingCheckIn = true;
            try {
              const reservation = this.scanResult.data.reservation;
              if (!reservation?.check_in_qr_code) {
                // Generate check-in QR first
                const genRes = await fetch('{{ route('waiter.table-scanner.generate-checkin-qr') }}', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  },
                  body: JSON.stringify({
                    reservation_id: reservation.id
                  }),
                });
                const genData = await genRes.json();
                if (!genData.success) {
                  alert(genData.message);
                  return;
                }
                await this.processCheckInQr(genData.data.qr_code);
              } else {
                await this.processCheckInQr(reservation.check_in_qr_code);
              }
              this.scanResult = null;
            } finally {
              this.processingCheckIn = false;
            }
          },

          async processCheckInById(reservationId) {
            this.processingCheckIn = true;
            try {
              const genRes = await fetch('{{ route('waiter.table-scanner.generate-checkin-qr') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                  reservation_id: reservationId
                }),
              });
              const genData = await genRes.json();
              if (!genData.success) {
                alert(genData.message);
                return;
              }

              const checkRes = await fetch('{{ route('waiter.table-scanner.process-checkin') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                  qr_code: genData.data.qr_code
                }),
              });
              const checkData = await checkRes.json();
              if (checkData.success) {
                this.checkInSuccess = true;
                this.waiterAssigned = checkData.data?.waiter_assigned ?? false;
                this.checkInSuccessMsg = `${checkData.data?.customer ?? ''} - Meja ${checkData.data?.table ?? ''}`;
                this.manualSearch = '';
                this.searchResults = [];
                setTimeout(() => {
                  this.checkInSuccess = false;
                  this.waiterAssigned = false;
                }, 5000);
              } else {
                alert(checkData.message);
              }
            } finally {
              this.processingCheckIn = false;
            }
          },

          async searchBooking() {
            if (this.manualSearch.length < 2) {
              this.searchResults = [];
              return;
            }
            try {
              const res = await fetch('{{ route('waiter.bookings.index') }}?' + new URLSearchParams({
                search: this.manualSearch,
                status: 'confirmed',
                format: 'json'
              }));
              if (res.ok) {
                const data = await res.json();
                this.searchResults = data.reservations ?? [];
              }
            } catch (_) {
              this.searchResults = [];
            }
          },
        }
      }
    </script>
  @endpush
</x-waiter-mobile-layout>
