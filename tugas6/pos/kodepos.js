
const API_URL = "https://www.emsifa.com/api-wilayah-indonesia/v2";

const provinceSelect = document.getElementById("province");
const regencySelect = document.getElementById("regency");
const districtSelect = document.getElementById("district");
const searchForm = document.getElementById("searchForm");
const searchButton = document.getElementById("searchButton");
const statusText = document.getElementById("status");
const results = document.getElementById("results");
const resultBody = document.getElementById("resultBody");
const areaInfo = document.getElementById("areaInfo");

let selectedProvince = "";
let selectedRegency = "";
let selectedDistrict = "";

async function getData(url) {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error("Gagal mengambil data wilayah.");
    }

    const result = await response.json();
    return result.data ?? result;
}

function fillDropdown(select, data, placeholder) {
    select.innerHTML = "";

    const firstOption = document.createElement("option");
    firstOption.value = "";
    firstOption.textContent = placeholder;
    select.appendChild(firstOption);

    data.forEach(function (item) {
        const option = document.createElement("option");
        option.value = item.id;
        option.textContent = item.name;
        select.appendChild(option);
    });

    select.disabled = false;
}

function resetDropdown(select, placeholder) {
    select.innerHTML = "";

    const option = document.createElement("option");
    option.value = "";
    option.textContent = placeholder;

    select.appendChild(option);
    select.disabled = true;
}

function clearResults() {
    results.hidden = true;
    resultBody.innerHTML = "";
    areaInfo.textContent = "";
}

async function loadProvinces() {
    try {
        statusText.textContent = "Memuat data provinsi...";

        const provinces = await getData(
            `${API_URL}/provinces.json`
        );

        fillDropdown(
            provinceSelect,
            provinces,
            "Pilih provinsi"
        );

        statusText.textContent = "Silakan pilih provinsi.";
    } catch (error) {
        statusText.textContent =
            "Data provinsi gagal dimuat. Periksa koneksi internet dan coba muat ulang halaman.";
    }
}

provinceSelect.addEventListener("change", async function () {
    selectedProvince = provinceSelect.selectedOptions[0]?.textContent || "";
    selectedRegency = "";
    selectedDistrict = "";

    resetDropdown(regencySelect, "Memuat kabupaten/kota...");
    resetDropdown(districtSelect, "Pilih kecamatan");
    searchButton.disabled = true;
    clearResults();

    if (!provinceSelect.value) {
        resetDropdown(regencySelect, "Pilih kabupaten/kota");
        statusText.textContent = "Silakan pilih provinsi.";
        return;
    }

    try {
        statusText.textContent = "Memuat kabupaten/kota...";

        const regencies = await getData(
            `${API_URL}/regencies/${provinceSelect.value}.json`
        );

        fillDropdown(
            regencySelect,
            regencies,
            "Pilih kabupaten/kota"
        );

        statusText.textContent = "Silakan pilih kabupaten/kota.";
    } catch (error) {
        resetDropdown(regencySelect, "Data gagal dimuat");
        statusText.textContent = error.message;
    }
});

regencySelect.addEventListener("change", async function () {
    selectedRegency = regencySelect.selectedOptions[0]?.textContent || "";
    selectedDistrict = "";

    resetDropdown(districtSelect, "Memuat kecamatan...");
    searchButton.disabled = true;
    clearResults();

    if (!regencySelect.value) {
        resetDropdown(districtSelect, "Pilih kecamatan");
        statusText.textContent = "Silakan pilih kabupaten/kota.";
        return;
    }

    try {
        statusText.textContent = "Memuat kecamatan...";

        const districts = await getData(
            `${API_URL}/districts/${regencySelect.value}.json`
        );

        fillDropdown(
            districtSelect,
            districts,
            "Pilih kecamatan"
        );

        statusText.textContent = "Silakan pilih kecamatan.";
    } catch (error) {
        resetDropdown(districtSelect, "Data gagal dimuat");
        statusText.textContent = error.message;
    }
});

districtSelect.addEventListener("change", function () {
    selectedDistrict = districtSelect.selectedOptions[0]?.textContent || "";

    clearResults();
    searchButton.disabled = !districtSelect.value;

    if (districtSelect.value) {
        statusText.textContent =
            "Klik Cari Kode Pos untuk melihat hasil.";
    } else {
        statusText.textContent = "Silakan pilih kecamatan.";
    }
});

searchForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (
        !provinceSelect.value ||
        !regencySelect.value ||
        !districtSelect.value
    ) {
        statusText.textContent = "Pilih semua wilayah terlebih dahulu.";
        return;
    }

    searchButton.disabled = true;
    clearResults();

    try {
        statusText.textContent = "Mencari kode pos...";

        const villages = await getData(
            `${API_URL}/villages/${districtSelect.value}.json`
        );

        areaInfo.textContent =
            `Provinsi: ${selectedProvince} | ` +
            `Kabupaten/Kota: ${selectedRegency} | ` +
            `Kecamatan: ${selectedDistrict}`;

        if (villages.length === 0) {
            statusText.textContent =
                "Tidak ada data desa/kelurahan untuk wilayah ini.";
            return;
        }

        villages.forEach(function (village) {
            const row = document.createElement("tr");
            const villageCell = document.createElement("td");
            const postalCell = document.createElement("td");

            villageCell.textContent = village.name;
            postalCell.textContent =
                village.postal_code || "Kode pos tidak tersedia";

            row.appendChild(villageCell);
            row.appendChild(postalCell);
            resultBody.appendChild(row);
        });

        results.hidden = false;
        statusText.textContent =
            `Ditemukan ${villages.length} desa/kelurahan.`;
    } catch (error) {
        statusText.textContent =
            "Pencarian gagal. Periksa koneksi internet dan coba lagi.";
    } finally {
        searchButton.disabled = !districtSelect.value;
    }
});

loadProvinces();