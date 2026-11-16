const dashboardTranslations = {
    id: {
        choose_language: 'Pilih bahasa dashboard',
        today_summary: 'Ringkasan hari ini',
        sales_chart_label: 'Grafik penjualan enam bulan terakhir',
        store_overview: 'RINGKASAN TOKO',
        welcome: 'Selamat datang kembali,',
        hero_description: 'Pantau penjualan, pelanggan, dan persediaan toko Anda dalam satu tempat.',
        new_sale: 'Transaksi baru',
        today: 'HARI INI',
        sales_today: 'Penjualan hari ini',
        sales_total_hint: 'Total dari transaksi tercatat',
        orders_today: 'Transaksi hari ini',
        orders_hint: 'Transaksi penjualan berhasil',
        all_time: 'TOTAL',
        customers: 'Pelanggan terdaftar',
        customers_hint: 'Data pelanggan toko',
        inventory: 'INVENTARIS',
        low_stock: 'Stok menipis',
        products_total: 'produk terdaftar',
        stock_alert_title: 'Perlu perhatian pada stok',
        stock_alert_text: 'produk berada di bawah batas stok minimum.',
        check_stock: 'Periksa stok',
        sales_analytics: 'ANALISIS PENJUALAN',
        sales_trend: 'Tren penjualan',
        sales_chart_hint: 'Total penjualan per bulan selama enam bulan terakhir.',
        last_six_months: '6 bulan terakhir',
        inventory_overview: 'RINGKASAN INVENTARIS',
        stock_health: 'Kondisi stok',
        products_low: 'stok perlu ditambah',
        stock_ready: 'Stok aman',
        manage_inventory: 'Kelola inventaris',
        latest_activity: 'AKTIVITAS TERBARU',
        recent_transactions: 'Transaksi terbaru',
        view_all: 'Lihat semua',
        invoice: 'No. Faktur',
        general_customer: 'Pelanggan umum',
        customer: 'Pelanggan',
        date: 'Tanggal',
        payment: 'Pembayaran',
        amount: 'Jumlah',
        no_transactions: 'Belum ada transaksi.',
        shortcuts: 'AKSES CEPAT',
        quick_actions: 'Aksi cepat',
        open_pos: 'Buka kasir',
        open_pos_hint: 'Mulai transaksi penjualan',
        manage_products: 'Kelola produk',
        manage_products_hint: 'Lihat produk dan persediaan',
        view_reports: 'Lihat laporan',
        view_reports_hint: 'Tinjau performa penjualan',
        chart_sales: 'Penjualan'
    },
    en: {
        choose_language: 'Choose dashboard language',
        today_summary: 'Today’s summary',
        sales_chart_label: 'Sales chart for the last six months',
        store_overview: 'STORE OVERVIEW',
        welcome: 'Welcome back,',
        hero_description: 'Monitor your store sales, customers, and inventory in one place.',
        new_sale: 'New sale',
        today: 'TODAY',
        sales_today: 'Today’s sales',
        sales_total_hint: 'Total from recorded transactions',
        orders_today: 'Today’s transactions',
        orders_hint: 'Completed sales transactions',
        all_time: 'ALL TIME',
        customers: 'Registered customers',
        customers_hint: 'Customers in your store',
        inventory: 'INVENTORY',
        low_stock: 'Low-stock products',
        products_total: 'products listed',
        stock_alert_title: 'Inventory needs attention',
        stock_alert_text: 'products are below the minimum stock level.',
        check_stock: 'Check stock',
        sales_analytics: 'SALES ANALYTICS',
        sales_trend: 'Sales trend',
        sales_chart_hint: 'Monthly sales total over the last six months.',
        last_six_months: 'Last 6 months',
        inventory_overview: 'INVENTORY OVERVIEW',
        stock_health: 'Stock health',
        products_low: 'need replenishment',
        stock_ready: 'Healthy stock',
        manage_inventory: 'Manage inventory',
        latest_activity: 'LATEST ACTIVITY',
        recent_transactions: 'Recent transactions',
        view_all: 'View all',
        invoice: 'Invoice',
        general_customer: 'Walk-in customer',
        customer: 'Customer',
        date: 'Date',
        payment: 'Payment',
        amount: 'Amount',
        no_transactions: 'No transactions yet.',
        shortcuts: 'SHORTCUTS',
        quick_actions: 'Quick actions',
        open_pos: 'Open POS',
        open_pos_hint: 'Start a sales transaction',
        manage_products: 'Manage products',
        manage_products_hint: 'View products and inventory',
        view_reports: 'View reports',
        view_reports_hint: 'Review sales performance',
        chart_sales: 'Sales'
    }
};

let dashboardSalesChart = null;
let dashboardLanguage = 'id';

function setDashboardLanguage(language) {
    if (!dashboardTranslations[language]) return;

    dashboardLanguage = language;
    const translation = dashboardTranslations[language];
    const locale = language === 'id' ? 'id-ID' : 'en-US';

    document.documentElement.lang = language;
    document.querySelectorAll('#dashboard [data-i18n]').forEach(element => {
        const key = element.dataset.i18n;
        if (translation[key]) element.textContent = translation[key];
    });

    document.querySelectorAll('#dashboard [data-i18n-aria-label]').forEach(element => {
        const key = element.dataset.i18nAriaLabel;
        if (translation[key]) element.setAttribute('aria-label', translation[key]);
    });

    document.querySelectorAll('[data-dashboard-language]').forEach(button => {
        const active = button.dataset.dashboardLanguage === language;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
    });

    document.querySelectorAll('#dashboard [data-dashboard-number]').forEach(element => {
        element.textContent = new Intl.NumberFormat(locale).format(Number(element.dataset.dashboardNumber));
    });

    document.querySelectorAll('#dashboard [data-dashboard-currency]').forEach(element => {
        element.textContent = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(element.dataset.dashboardCurrency));
    });

    const dashboardDate = document.getElementById('dashboardDate');
    if (dashboardDate) {
        const [year, month, day] = dashboardDate.dateTime.split('-').map(Number);
        dashboardDate.textContent = new Intl.DateTimeFormat(locale, {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date(year, month - 1, day));
    }

    document.querySelectorAll('.dashboard-transaction-date').forEach(element => {
        const [year, month, day] = element.dateTime.split('-').map(Number);
        if (year && month && day) {
            element.textContent = new Intl.DateTimeFormat(locale).format(new Date(year, month - 1, day));
        }
    });

    localStorage.setItem('agsmart-dashboard-language', language);

    if (dashboardSalesChart) {
        dashboardSalesChart.data.labels = getDashboardMonthLabels(locale);
        dashboardSalesChart.data.datasets[0].label = translation.chart_sales;
        dashboardSalesChart.update();
    }

    const chart = document.getElementById('dashboardSalesChart');
    if (chart) {
        chart.setAttribute('aria-label', translation.sales_chart_label);
    }
}

function getDashboardMonthLabels(locale) {
    return (window.dashboardData?.months || []).map(period => {
        const [year, month] = period.split('-').map(Number);
        return new Intl.DateTimeFormat(locale, { month: 'short' }).format(new Date(year, month - 1, 1));
    });
}

function initializeDashboardChart() {
    const canvas = document.getElementById('dashboardSalesChart');
    if (!canvas || !window.Chart) return;

    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(255, 120, 73, 0.24)');
    gradient.addColorStop(1, 'rgba(255, 120, 73, 0)');

    dashboardSalesChart = new Chart(context, {
        type: 'line',
        data: {
            labels: getDashboardMonthLabels('id-ID'),
            datasets: [{
                label: dashboardTranslations.id.chart_sales,
                data: window.dashboardData?.sales || [],
                borderColor: '#f4774c',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.38,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#f4774c',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    displayColors: false,
                    callbacks: {
                        label: context => `${dashboardTranslations[dashboardLanguage].chart_sales}: ${new Intl.NumberFormat(dashboardLanguage === 'id' ? 'id-ID' : 'en-US', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0
                        }).format(context.parsed.y)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#8994a5', font: { family: 'Plus Jakarta Sans', size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef1f5', drawBorder: false },
                    border: { display: false },
                    ticks: {
                        color: '#8994a5',
                        maxTicksLimit: 5,
                        font: { family: 'Plus Jakarta Sans', size: 10 },
                        callback: value => new Intl.NumberFormat(dashboardLanguage === 'id' ? 'id-ID' : 'en-US', {
                            notation: 'compact',
                            maximumFractionDigits: 1
                        }).format(value)
                    }
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeDashboardChart();
    const savedLanguage = localStorage.getItem('agsmart-dashboard-language');
    setDashboardLanguage(savedLanguage === 'en' ? 'en' : 'id');
});
