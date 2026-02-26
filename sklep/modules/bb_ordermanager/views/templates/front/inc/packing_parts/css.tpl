<style>
    /* RESET & BASE */
    * { box-sizing: border-box; }
    body { font-family: 'Open Sans', sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; height: 100vh; width: 100vw; overflow: hidden; display: flex; }
    .sidebar { width: 300px; background: white; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; flex-shrink: 0; z-index: 10; }
    .sidebar-header { padding: 15px 20px; border-bottom: 1px solid #eee; background: #fcfcfc; color: #555; font-weight: 700; font-size: 12px; text-transform: uppercase; }
    .order-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
    .order-item { display: block; padding: 12px 20px; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: #444; transition: all 0.3s; border-left: 4px solid transparent; order: 0; }
    .order-item:hover { filter: brightness(0.98); }
    .order-item.active { background-color: #e3f2fd !important; border-left-color: #2196f3 !important; order: -1; }
    .order-item.status-partial { background-color: #fff7ed; border-left-color: #f97316; order: 1; }
    .order-item.status-done { background-color: #f0fdf4; border-left-color: #22c55e; opacity: 0.7; order: 10; }
    .order-item.status-new { background-color: #ffffff; order: 0; }
    .order-ref { display: flex; justify-content: space-between; font-weight: 700; font-size: 14px; margin-bottom: 4px; }
    .order-id { color: #999; font-weight: 400; font-size: 12px; }
    .order-customer { font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; justify-content: space-between; }
    .sidebar-counter { font-weight: 700; font-size: 11px; background: rgba(0,0,0,0.05); padding: 1px 6px; border-radius: 4px; }
    .status-partial .sidebar-counter { color: #ea580c; background: #ffedd5; }
    .status-done .sidebar-counter { color: #15803d; background: #dcfce7; }
    .main-content { flex: 1; display: flex; flex-direction: column; background: #f4f6f8; min-width: 0; position: relative; }
    .top-bar { height: 60px; background: white; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; flex-shrink: 0; }
    .order-title { font-size: 20px; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
    .ref-link { color: #2c3e50; text-decoration: none; border-bottom: 1px dashed #ccc; transition: all 0.2s; }
    .ref-link:hover { color: #2196f3; border-color: #2196f3; }
    .close-btn { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .close-btn:hover { background: #e2e8f0; color: #334155; }
    .packing-area { flex: 1; overflow-y: auto; padding: 20px; }
    .products-card { background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; width: 100%; display: flex; flex-direction: column; }
    
    .product-row { display: flex; align-items: center; padding: 15px 25px; border-bottom: 1px solid #f1f5f9; transition: background-color 0.3s; order: 0; border-left: 4px solid transparent; }
    .product-row.packed-partial { background-color: #fff7ed; border-left-color: #f97316; }
    .product-row.packed-done { background-color: #ecfdf5; border-left-color: #10b981; order: 1; }
    
    .prod-img { width: 60px; height: 60px; object-fit: contain; border: 1px solid #e2e8f0; border-radius: 6px; padding: 2px; margin-right: 20px; background: white; flex-shrink: 0; }
    .prod-info { flex: 1; min-width: 0; }
    .prod-name { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 6px; line-height: 1.4; }
    .prod-meta { display: flex; gap: 10px; font-size: 12px; color: #64748b; }
    .tag { background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-weight: 600; }
    .counter-box { display: flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin: 0 20px; flex-shrink: 0; }
    .cnt-btn { width: 36px; height: 36px; background: white; border: none; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; }
    .cnt-btn:hover { background-color: #f8fafc; color: #334155; }
    .cnt-val { padding: 0 15px; min-width: 80px; text-align: center; font-weight: 700; font-size: 16px; color: #334155; background: white; line-height: 36px; }
    .cnt-val.done { color: #10b981; }
    .status-icon { width: 40px; flex-shrink: 0; text-align: center; }
    
    .status-btn { width: 36px; height: 36px; border-radius: 50%; border: 2px solid #e2e8f0; color: #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 18px; background: white; cursor: pointer; transition: all 0.2s; }
    .status-btn:hover { border-color: #10b981; color: #10b981; background-color: #f0fdf4; transform: scale(1.1); }
    .status-btn.checked { border-color: #10b981; background-color: #10b981; color: white; cursor: default; }
    .status-btn.checked:hover { transform: none; }
    
    #scanner-input { position: absolute; top: -9999px; opacity: 0; }
    .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px); background: #1e293b; color: white; padding: 12px 24px; border-radius: 50px; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transition: all 0.3s; z-index: 1000; display: flex; align-items: center; gap: 10px; }
    .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    .toast.success { background: #10b981; }
    .toast.warning { background: #f59e0b; }
    .error-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(220, 38, 38, 0.95); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.1s; backdrop-filter: blur(5px); }
    .error-overlay.show { opacity: 1; pointer-events: auto; }
    .success-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(16, 185, 129, 0.95); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s; backdrop-filter: blur(5px); flex-direction: column; }
    .success-overlay.show { opacity: 1; pointer-events: auto; }
    .modal-box { text-align: center; color: white; transform: scale(0.8); transition: transform 0.2s; }
    .show .modal-box { transform: scale(1); }
    .modal-icon { font-size: 100px; margin-bottom: 20px; color: white; }
    .modal-title { font-size: 40px; font-weight: 900; margin-bottom: 10px; text-transform: uppercase; }
    .scanned-code { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; font-family: monospace; font-size: 24px; font-weight: bold; border-radius: 8px; display: inline-block; }
    .btn-home { margin-top: 30px; background: white; color: #10b981; font-weight: 800; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-size: 18px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: transform 0.2s; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; border: none; }
    .btn-home:hover { transform: scale(1.05); }
</style>