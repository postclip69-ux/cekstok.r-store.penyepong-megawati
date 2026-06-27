// api/proxy.js

function parseArea(desc = '') {

    const result = {
        area1: '',
        area2: '',
        area3: '',
        area4: ''
    };

    desc.split(/\r?\n/).forEach(line => {

        const clean = line
            .replace(/~/g, '')
            .trim();

        const match = clean.match(
            /AREA\s*(\d)\s*[:=]\s*(.+)/i
        );

        if (!match) return;

        const nomor = match[1];
        const value = match[2].trim();

        result[`area${nomor}`] = value;

    });

    return result;
}

export default async function handler(req, res) {

    const stockUrl =
        'https://panel.khfy-store.com/api_v3/cek_stock_akrab';

    const productUrl =
        'https://panel.khfy-store.com/api_v2/list_product?api_key=6A225EC0-2922-4252-8204-C7C00A3DA0E5';

    const targetUrlXda =
        'https://juraganxl.my.id/api/regulers';

    const apiKeyXda =
        'bunderkananbunderkanankirikotaksegitigaatas';

    try {

        const [
            stockRes,
            productRes,
            xdaRes
        ] = await Promise.all([
            fetch(stockUrl),
            fetch(productUrl),
            fetch(targetUrlXda, {
                headers: {
                    'x-api-key': apiKeyXda
                }
            })
        ]);

        const stockData = await stockRes.json();
        const productData = await productRes.json();
        const dataXda = await xdaRes.json();

        // ==========================
        // DETAIL AREA XLA
        // ==========================

        const detailMapXla = {};

        if (productData.ok && Array.isArray(productData.data)) {

            productData.data
                .filter(item => item.kode_produk.startsWith('XLA'))
                .forEach(item => {

                    detailMapXla[item.kode_produk] =
                        parseArea(item.deskripsi);

                });

        }

        // ==========================
        // DETAIL AREA XDA
        // ==========================

        const detailMapXda = {};

        if (productData.ok && Array.isArray(productData.data)) {

            productData.data
                .filter(item => item.kode_produk.startsWith('XDA'))
                .forEach(item => {

                    detailMapXda[item.kode_produk] =
                        parseArea(item.deskripsi);

                });

        }

        // ==========================
        // FIX DATA XDA76
        // ==========================

        detailMapXda['XDA76'] = {
            area1: '76 GB',
            area2: '78 GB',
            area3: '83 GB',
            area4: '93 GB'
        };

        // ==========================
        // GABUNGKAN XLA
        // ==========================

        let xlaData = [];

        if (stockData.ok && Array.isArray(stockData.data)) {

            xlaData = stockData.data.map(item => ({

                ...item,

                area1: detailMapXla[item.type]?.area1 || '',
                area2: detailMapXla[item.type]?.area2 || '',
                area3: detailMapXla[item.type]?.area3 || '',
                area4: detailMapXla[item.type]?.area4 || ''

            }));

        }

        // ==========================
        // GABUNGKAN XDA
        // ==========================

        let xdaData = [];

        if (Array.isArray(dataXda)) {

            xdaData = dataXda.map(item => ({

                type: 'Reguler',

                nama: item.config,

                sisa_slot: item.count.toString(),

                area1: detailMapXda[item.config]?.area1 || '',
                area2: detailMapXda[item.config]?.area2 || '',
                area3: detailMapXda[item.config]?.area3 || '',
                area4: detailMapXda[item.config]?.area4 || ''

            }));

        }

        // ==========================
        // RESPONSE
        // ==========================

        res.status(200).json({
            ok: true,
            xla: xlaData,
            xda: xdaData
        });

    } catch (error) {

        console.error(error);

        res.status(500).json({
            error: 'Gagal mengambil data dari server pusat'
        });

    }

}
