# 项目目录结构

```
退换货管理系统/
├── backend/                      # 后端 Laravel 应用
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── InstallController.php
│   │   │   │   ├── PlatformController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── WarehouseController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── AuthController.php
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Store.php
│   │   │   ├── Product.php
│   │   │   ├── Warehouse.php
│   │   │   ├── Order.php
│   │   │   └── ReturnOrder.php
│   │   ├── Services/
│   │   │   ├── PlatformService.php
│   │   │   ├── ProductService.php
│   │   │   ├── SyncService.php
│   │   │   └── InstallService.php
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   └── public/
├── frontend/                     # 前端 Vue 应用
│   ├── src/
│   │   ├── views/
│   │   │   ├── install/
│   │   │   ├── dashboard/
│   │   │   ├── platforms/
│   │   │   ├── products/
│   │   │   ├── warehouses/
│   │   │   └── orders/
│   │   ├── components/
│   │   ├── api/
│   │   ├── router/
│   │   └── stores/
│   ├── public/
│   └── package.json
├── install/                      # 安装程序
│   ├── index.php
│   ├── steps/
│   │   ├── 1-welcome.php
│   │   ├── 2-database.php
│   │   ├── 3-admin.php
│   │   ├── 4-website.php
│   │   └── 5-complete.php
│   └── templates/
├── docs/                         # 文档
│   ├── INSTALL.md
│   ├── API.md
│   └── DEPLOY.md
└── public/                       # Web 入口
    ├── index.php
    ├── assets/
    └── .htaccess
```
