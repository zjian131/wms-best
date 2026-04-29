# 跨境电商退换货管理系统

一个功能完善的跨境电商退换货管理系统，支持多平台对接、商品管理、仓库管理等核心功能。

## 功能特性

### 1. 系统部署与安装
- 适配 Linux 系统宝塔面板安装环境
- 可视化安装向导
- 支持自定义配置安装参数
- 一键式安装功能

### 2. 电商平台对接
- 支持主流跨境电商平台（Amazon、eBay、Shopify、Shopee、Lazada）
- 多平台多店铺统一管理
- 订单数据同步机制
- 退货退款订单专项获取

### 3. 商品管理系统
- 退货商品自动导入
- 商品库存管理
- 商品重新售卖功能
- 库存联动机制

### 4. 仓库地址管理
- 退货地址自动获取
- 手动添加/编辑仓库地址
- 多仓库地址管理
- 仓库与商品、订单关联

### 5. 数据安全与日志
- 敏感信息加密存储
- API调用权限控制
- 完善的操作日志系统

## 技术架构

- **后端**：PHP 8.2+ / Laravel 11
- **前端**：Vue 3 + Element Plus + Vite
- **数据库**：MySQL 8.0+
- **部署环境**：Linux + 宝塔面板

## 快速开始

### 安装部署

详细安装指南请参考 [docs/INSTALL.md](docs/INSTALL.md)

1. 上传项目文件到服务器
2. 配置目录权限
3. 访问 `/install` 开始安装
4. 按照向导完成配置

### 本地开发

```bash
# 后端
cd backend
composer install
php artisan serve

# 前端
cd frontend
npm install
npm run dev
```

## 项目结构

```
退换货管理系统/
├── backend/              # Laravel后端
│   ├── app/
│   │   ├── Models/      # 数据模型
│   │   ├── Services/    # 业务逻辑
│   │   └── Http/        # 控制器和路由
│   ├── database/        # 数据库迁移
│   └── routes/          # API路由
├── frontend/            # Vue前端
│   ├── src/
│   │   ├── views/       # 页面组件
│   │   ├── stores/      # 状态管理
│   │   └── api/         # API调用
│   └── public/
├── install/             # 安装向导
│   ├── steps/           # 安装步骤
│   └── templates/       # 模板文件
├── docs/                # 文档
└── public/              # Web入口
```

## API文档

主要API接口：

### 认证
- `POST /api/auth/login` - 用户登录
- `POST /api/auth/logout` - 用户登出
- `GET /api/auth/me` - 获取当前用户信息

### 店铺
- `GET /api/stores` - 获取店铺列表
- `POST /api/stores` - 创建店铺
- `POST /api/stores/{id}/sync` - 同步店铺数据

### 商品
- `GET /api/products` - 获取商品列表
- `POST /api/products` - 创建商品
- `POST /api/products/{id}/bind-order` - 绑定订单
- `POST /api/products/{id}/update-stock` - 更新库存

### 仓库
- `GET /api/warehouses` - 获取仓库列表
- `POST /api/warehouses` - 创建仓库

### 订单和退货
- `GET /api/orders` - 获取订单列表
- `GET /api/returns` - 获取退货列表
- `POST /api/returns/{id}/mark-received` - 标记已收货
- `POST /api/returns/{id}/mark-restocked` - 标记已入库

## 支持的平台

- Amazon（亚马逊）
- eBay
- Shopify
- Shopee（虾皮）
- Lazada（来赞达）

## 开发计划

- [ ] 更多电商平台支持
- [ ] 报表和统计功能
- [ ] 多语言支持
- [ ] 移动端App
- [ ] AI智能分析

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License
