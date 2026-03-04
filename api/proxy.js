// api/proxy.js
export default async function handler(req, res) {
    // 1. URL Target Lama (Akrab KHFY)
    const targetUrlAkrab = 'https://panel.khfy-store.com/api_v3/cek_stock_akrab';

    // 2. URL Target Baru (XDA / Reguler JuraganXL)
    const targetUrlXda = 'https://juraganxl.my.id/api/regulers';
    const apiKeyXda = 'bunderkananbunderkanankirikotaksegitigaatas';

    try {
        // Fetch kedua API secara bersamaan (paralel) supaya loading tetap cepat
        const [responseAkrab, responseXda] = await Promise.all([
            fetch(targetUrlAkrab),
            fetch(targetUrlXda, {
                headers: {
                    'x-api-key': apiKeyXda
                }
            })
        ]);

        const dataAkrab = await responseAkrab.json();
        const dataXda = await responseXda.json();

        // Siapkan array kosong untuk menampung semua data gabungan
        let combinedData = [];

        // Masukkan data Akrab yang lama jika formatnya sesuai
        if (dataAkrab && dataAkrab.ok && Array.isArray(dataAkrab.data)) {
            combinedData = [...dataAkrab.data];
        } else if (Array.isArray(dataAkrab)) {
            // Jaga-jaga kalau response server lama bentuknya langsung array
            combinedData = [...dataAkrab];
        }

        // Format data XDA (JuraganXL) agar cocok dengan desain kartu di index.html
        // config -> diubah jadi nama
        // count -> diubah jadi sisa_slot
        if (Array.isArray(dataXda)) {
            const formattedXda = dataXda.map(item => ({
                type: 'XDA', // Muncul sebagai label kecil di atas nama produk
                nama: item.config, 
                sisa_slot: item.count.toString() 
            }));
            
            // Gabungkan data XDA ke dalam combinedData
            combinedData = [...combinedData, ...formattedXda];
        }

        // Kirim balik data gabungan ke frontend dengan format { ok: true, data: [...] }
        res.status(200).json({
            ok: true,
            data: combinedData
        });

    } catch (error) {
        console.error("Error fetching data:", error);
        res.status(500).json({ error: 'Gagal mengambil data dari server pusat' });
    }
}
