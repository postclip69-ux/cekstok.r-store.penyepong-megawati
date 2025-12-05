// api/proxy.js
export default async function handler(req, res) {
    // URL Target
    const targetUrl = 'https://panel.khfy-store.com/api_v3/cek_stock_akrab';

    try {
        const response = await fetch(targetUrl);
        const data = await response.json();
        
        // Kirim balik data ke frontend kita
        res.status(200).json(data);
    } catch (error) {
        // Kalau gagal
        res.status(500).json({ error: 'Gagal mengambil data dari server pusat' });
    }
}
