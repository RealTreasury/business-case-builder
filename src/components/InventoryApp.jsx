import React, { useState, useEffect, useCallback } from 'react';
import {
    Search, Plus, Upload, Download, Camera, BarChart3,
    TrendingUp, Package, AlertTriangle, CheckCircle,
    Filter, Sort, Grid, List, Settings, Home, ShoppingCart,
    Calendar, PieChart, Activity, Trash2, Edit, Eye
} from 'lucide-react';

// Enhanced Inventory Management App
const InventoryApp = () => {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [view, setView] = useState('dashboard'); // dashboard, inventory, analytics, scan, import
    const [searchTerm, setSearchTerm] = useState('');
    const [filterStatus, setFilterStatus] = useState('all');
    const [viewMode, setViewMode] = useState('grid'); // grid, list
    const [selectedItems, setSelectedItems] = useState(new Set());

    // Fetch items from API
    const fetchItems = useCallback(async () => {
        setLoading(true);
        try {
            const response = await fetch(`${window.pitApp?.restUrl}items`, {
                headers: { 'X-WP-Nonce': window.pitApp?.nonce }
            });
            const data = await response.json();
            setItems(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching items:', error);
            setItems([]);
        }
        setLoading(false);
    }, []);

    useEffect(() => {
        fetchItems();
    }, [fetchItems]);

    // Analytics calculations
    const analytics = {
        totalItems: items.length,
        totalQuantity: items.reduce((sum, item) => sum + (item.qty || 0), 0),
        lowStock: items.filter(item => (item.qty || 0) <= 5).length,
        recentPurchases: items.filter(item => item.purchased).length,
        categories: [...new Set(items.map(item => item.category).filter(Boolean))]
    };

    // Filter and search items
    const filteredItems = items.filter(item => {
        const matchesSearch = item.title?.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesFilter =
            filterStatus === 'all' ||
            (filterStatus === 'low-stock' && (item.qty || 0) <= 5) ||
            (filterStatus === 'purchased' && item.purchased) ||
            (filterStatus === 'needed' && !item.purchased && (item.qty || 0) <= 5);
        return matchesSearch && matchesFilter;
    });

    // Header Component
    const Header = () => (
        <header className="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
            <div className="px-4 sm:px-6 lg:px-8 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Package className="h-8 w-8 text-blue-600" />
                        <h1 className="text-2xl font-bold text-gray-900">Inventory Tracker</h1>
                    </div>
                    <nav className="flex space-x-1">
                        {[
                            { key: 'dashboard', icon: Home, label: 'Dashboard' },
                            { key: 'inventory', icon: Package, label: 'Inventory' },
                            { key: 'analytics', icon: BarChart3, label: 'Analytics' },
                            { key: 'scan', icon: Camera, label: 'Scan' },
                            { key: 'import', icon: Upload, label: 'Import' }
                        ].map(({ key, icon: Icon, label }) => (
                            <button
                                key={key}
                                onClick={() => setView(key)}
                                className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2 ${
                                    view === key
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                }`}
                            >
                                <Icon className="h-4 w-4" />
                                <span className="hidden sm:inline">{label}</span>
                            </button>
                        ))}
                    </nav>
                </div>
            </div>
        </header>
    );

    // Dashboard Component
    const Dashboard = () => (
        <div className="space-y-6">
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-xl text-white">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-blue-100 text-sm">Total Items</p>
                            <p className="text-3xl font-bold">{analytics.totalItems}</p>
                        </div>
                        <Package className="h-8 w-8 text-blue-200" />
                    </div>
                </div>

                <div className="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-xl text-white">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-green-100 text-sm">Total Quantity</p>
                            <p className="text-3xl font-bold">{analytics.totalQuantity}</p>
                        </div>
                        <CheckCircle className="h-8 w-8 text-green-200" />
                    </div>
                </div>

                <div className="bg-gradient-to-r from-yellow-500 to-yellow-600 p-6 rounded-xl text-white">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-yellow-100 text-sm">Low Stock</p>
                            <p className="text-3xl font-bold">{analytics.lowStock}</p>
                        </div>
                        <AlertTriangle className="h-8 w-8 text-yellow-200" />
                    </div>
                </div>

                <div className="bg-gradient-to-r from-purple-500 to-purple-600 p-6 rounded-xl text-white">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-purple-100 text-sm">Categories</p>
                            <p className="text-3xl font-bold">{analytics.categories.length}</p>
                        </div>
                        <PieChart className="h-8 w-8 text-purple-200" />
                    </div>
                </div>
            </div>

            {/* Recent Activity & Quick Actions */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                    <div className="space-y-4">
                        {items.slice(0, 5).map(item => (
                            <div key={item.id} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <div className="flex items-center space-x-3">
                                    <div className="w-2 h-2 bg-green-400 rounded-full"></div>
                                    <span className="text-sm text-gray-900">{item.title}</span>
                                </div>
                                <span className="text-sm text-gray-500">Qty: {item.qty || 0}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <button
                            onClick={() => setView('inventory')}
                            className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center"
                        >
                            <Plus className="h-6 w-6 mx-auto mb-2 text-blue-600" />
                            <span className="text-sm font-medium text-gray-900">Add Item</span>
                        </button>
                        <button
                            onClick={() => setView('scan')}
                            className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center"
                        >
                            <Camera className="h-6 w-6 mx-auto mb-2 text-green-600" />
                            <span className="text-sm font-medium text-gray-900">Scan Receipt</span>
                        </button>
                        <button
                            onClick={() => setView('analytics')}
                            className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center"
                        >
                            <BarChart3 className="h-6 w-6 mx-auto mb-2 text-purple-600" />
                            <span className="text-sm font-medium text-gray-900">View Analytics</span>
                        </button>
                        <button
                            onClick={() => exportCSV()}
                            className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center"
                        >
                            <Download className="h-6 w-6 mx-auto mb-2 text-orange-600" />
                            <span className="text-sm font-medium text-gray-900">Export Data</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Low Stock Alerts */}
            {analytics.lowStock > 0 && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <div className="flex items-center space-x-3 mb-4">
                        <AlertTriangle className="h-6 w-6 text-yellow-600" />
                        <h3 className="text-lg font-semibold text-yellow-800">Low Stock Alert</h3>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        {items.filter(item => (item.qty || 0) <= 5).slice(0, 6).map(item => (
                            <div key={item.id} className="bg-white p-3 rounded-lg border border-yellow-200">
                                <div className="flex justify-between items-center">
                                    <span className="font-medium text-gray-900">{item.title}</span>
                                    <span className="text-sm text-yellow-600">Qty: {item.qty || 0}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );

    // Inventory View Component
    const InventoryView = () => (
        <div className="space-y-6">
            {/* Controls */}
            <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                    <div className="flex items-center space-x-4 w-full sm:w-auto">
                        <div className="relative flex-1 sm:flex-initial">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <input
                                type="text"
                                placeholder="Search items..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full sm:w-64 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Items</option>
                            <option value="low-stock">Low Stock</option>
                            <option value="purchased">Recently Purchased</option>
                            <option value="needed">Need to Buy</option>
                        </select>
                    </div>

                    <div className="flex items-center space-x-2">
                        <button
                            onClick={() => setViewMode(viewMode === 'grid' ? 'list' : 'grid')}
                            className="p-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            {viewMode === 'grid' ? <List className="h-4 w-4" /> : <Grid className="h-4 w-4" />}
                        </button>
                        <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2">
                            <Plus className="h-4 w-4" />
                            <span>Add Item</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Items Grid/List */}
            <div className={viewMode === 'grid'
                ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
                : 'space-y-4'
            }>
                {filteredItems.map(item => (
                    <ItemCard key={item.id} item={item} viewMode={viewMode} />
                ))}
            </div>

            {filteredItems.length === 0 && (
                <div className="text-center py-12">
                    <Package className="mx-auto h-12 w-12 text-gray-400" />
                    <h3 className="mt-2 text-sm font-medium text-gray-900">No items found</h3>
                    <p className="mt-1 text-sm text-gray-500">Get started by adding your first inventory item.</p>
                </div>
            )}
        </div>
    );

    // Item Card Component
    const ItemCard = ({ item, viewMode }) => {
        const isLowStock = (item.qty || 0) <= 5;

        if (viewMode === 'list') {
            return (
                <div className="bg-white p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4 flex-1">
                            <div className={`w-4 h-4 rounded-full ${isLowStock ? 'bg-red-400' : 'bg-green-400'}`}></div>
                            <div className="flex-1">
                                <h3 className="font-medium text-gray-900">{item.title}</h3>
                                <p className="text-sm text-gray-500">Quantity: {item.qty || 0}</p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-2">
                            <button className="p-2 text-gray-400 hover:text-gray-600">
                                <Eye className="h-4 w-4" />
                            </button>
                            <button className="p-2 text-gray-400 hover:text-gray-600">
                                <Edit className="h-4 w-4" />
                            </button>
                            <button className="p-2 text-gray-400 hover:text-red-600">
                                <Trash2 className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div className="flex justify-between items-start mb-4">
                    <div className={`w-3 h-3 rounded-full ${isLowStock ? 'bg-red-400' : 'bg-green-400'}`}></div>
                    <div className="flex space-x-1">
                        <button className="p-1 text-gray-400 hover:text-gray-600">
                            <Edit className="h-4 w-4" />
                        </button>
                        <button className="p-1 text-gray-400 hover:text-red-600">
                            <Trash2 className="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-2">{item.title}</h3>

                <div className="space-y-2">
                    <div className="flex justify-between items-center">
                        <span className="text-sm text-gray-500">Quantity</span>
                        <div className="flex items-center space-x-2">
                            <button className="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-sm">-</button>
                            <span className="font-medium min-w-[2rem] text-center">{item.qty || 0}</span>
                            <button className="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-sm">+</button>
                        </div>
                    </div>

                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">Purchased</span>
                        <input
                            type="checkbox"
                            checked={item.purchased || false}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                    </div>
                </div>

                {isLowStock && (
                    <div className="mt-3 px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full inline-block">
                        Low Stock
                    </div>
                )}
            </div>
        );
    };

    // Export CSV function
    const exportCSV = () => {
        const csvContent = [
            ['Title', 'Quantity', 'Purchased'],
            ...items.map(item => [item.title, item.qty || 0, item.purchased ? 'Yes' : 'No'])
        ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'inventory.csv';
        a.click();
        URL.revokeObjectURL(url);
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <Header />
            <main className="px-4 sm:px-6 lg:px-8 py-8">
                {view === 'dashboard' && <Dashboard />}
                {view === 'inventory' && <InventoryView />}
                {view === 'analytics' && <div>Analytics view coming soon...</div>}
                {view === 'scan' && <div>OCR Scanning view coming soon...</div>}
                {view === 'import' && <div>Import view coming soon...</div>}
            </main>
        </div>
    );
};

export default InventoryApp;

