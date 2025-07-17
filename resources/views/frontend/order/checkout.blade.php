<x-app-layout>
  <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Checkout</h2>

    <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium">Nama Lengkap</label>
          <input type="text" name="fullName" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
          <label class="block font-medium">Email</label>
          <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
          <label class="block font-medium">No. Telepon</label>
          <input type="text" name="phone" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
          <label class="block font-medium">Provinsi</label>
          <select name="province" id="province" class="w-full border rounded px-3 py-2" required></select>
        </div>

        <div>
          <label class="block font-medium">Kota</label>
          <select name="city" id="city" class="w-full border rounded px-3 py-2" required></select>
        </div>

        <div>
          <label class="block font-medium">Kode Pos</label>
          <input type="text" name="postcode" class="w-full border rounded px-3 py-2">
        </div>

        <div class="md:col-span-2">
          <label class="block font-medium">Alamat</label>
          <textarea name="address" class="w-full border rounded px-3 py-2" rows="3" required></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="block font-medium">Alamat 2 (Opsional)</label>
          <input type="text" name="address2" class="w-full border rounded px-3 py-2">
        </div>

        <div class="md:col-span-2">
          <label class="block font-medium">Catatan (Opsional)</label>
          <textarea name="notes" class="w-full border rounded px-3 py-2" rows="3"></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="block font-medium">Jasa Pengiriman</label>
          <select name="shippingService" id="shippingService" class="w-full border rounded px-3 py-2" required></select>
        </div>

        <div class="md:col-span-2">
          <p id="shippingCostInfo" class="text-gray-600"></p>
        </div>
      </div>

      <div class="mt-6 text-right">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
          Proses Checkout
        </button>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      fetch('/api/provinces')
        .then(res => res.json())
        .then(data => {
          const provinceSelect = document.getElementById('province');
          data.provinces.forEach(province => {
            let opt = new Option(province.province, province.province_id);
            provinceSelect.add(opt);
          });
        });

      document.getElementById('province').addEventListener('change', function() {
        let provinceId = this.value;
        fetch(`/api/cities?province_id=${provinceId}`)
          .then(res => res.json())
          .then(data => {
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '';
            data.cities.forEach(city => {
              let opt = new Option(city.city_name, city.city_id);
              citySelect.add(opt);
            });
          });
      });

      document.getElementById('city').addEventListener('change', function() {
        let cityId = this.value;
        fetch(`/api/shipping-cost?destination=${cityId}`)
          .then(res => res.json())
          .then(data => {
            const serviceSelect = document.getElementById('shippingService');
            const info = document.getElementById('shippingCostInfo');
            serviceSelect.innerHTML = '';
            info.innerHTML = '';

            data.results.forEach(service => {
              let label = `${service.service} - Rp ${service.cost.toLocaleString()} - Estimasi ${service.etd} hari`;
              let opt = new Option(label, service.service.replace(/\s/g, ''));
              serviceSelect.add(opt);
            });

            if (data.results.length > 0) {
              info.innerHTML = `Estimasi: ${data.results[0].etd} hari - Biaya: Rp ${data.results[0].cost.toLocaleString()}`;
            }
          });
      });
    });
  </script>
</x-app-layout>
