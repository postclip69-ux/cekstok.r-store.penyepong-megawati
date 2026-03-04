// api/proxy.js
export default async function handler(req, res) {
    const targetUrlAkrab = 'https://panel.khfy-store.com/api_v3/cek_stock_akrab';
    const targetUrlXda = 'https://juraganxl.my.id/api/regulers';
    const apiKeyXda = 'bunderkananbunderkanankirikotaksegitigaatas';

    try {
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

        // 1. Data XLA
        let xlaData = [];
        if (dataAkrab && dataAkrab.ok && Array.isArray(dataAkrab.data)) {
            xlaData = [...dataAkrab.data];
        } else if (Array.isArray(dataAkrab)) {
            xlaData = [...dataAkrab];
        }

        // 2. Data XDA (Format disesuaikan)
        let xdaData = [];
        if (Array.isArray(dataXda)) {
            xdaData = dataXda.map(item => ({
                type: 'Reguler', // Label kecil di atas kartu
                nama: item.config, // Ini jadi ID uniknya (contoh: XDA31, AL1)
                sisa_slot: item.count.toString() 
            }));
        }

        // Kirim data terpisah
        res.status(200).json({
            ok: true,
            xla: xlaData,
            xda: xdaData
        });

    } catch (error) {
        console.error("Error fetching data:", error);
        res.status(500).json({ error: 'Gagal mengambil data dari server pusat' });
    }
}
