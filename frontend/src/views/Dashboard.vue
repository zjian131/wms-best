<template>
  <div class="dashboard">
    <el-row :gutter="20" style="margin-bottom: 20px">
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: #409EFF">
              <el-icon><Shop /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ dashboardData.stats?.stores || 0 }}</div>
              <div class="stat-label">店铺数量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: #67C23A">
              <el-icon><Goods /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ dashboardData.stats?.available_products || 0 }}</div>
              <div class="stat-label">可用商品</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: #E6A23C">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ dashboardData.stats?.total_orders || 0 }}</div>
              <div class="stat-label">订单总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: #F56C6C">
              <el-icon><RefreshLeft /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ dashboardData.stats?.pending_returns || 0 }}</div>
              <div class="stat-label">待处理退货</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="20">
      <el-col :span="16">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>最近退货</span>
            </div>
          </template>
          <el-table :data="dashboardData.recent_returns || []" style="width: 100%">
            <el-table-column prop="return_number" label="退货单号" />
            <el-table-column prop="platform_return_id" label="平台单号" />
            <el-table-column prop="status" label="状态">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="refund_amount" label="退款金额">
              <template #default="{ row }">
                {{ row.currency }} {{ row.refund_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="return_date" label="退货日期">
              <template #default="{ row }">
                {{ formatDate(row.return_date) }}
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>店铺列表</span>
              <el-button type="primary" size="small" link @click="$router.push('/stores')">管理</el-button>
            </div>
          </template>
          <div v-for="store in dashboardData.stores || []" :key="store.id" class="store-item">
            <div class="store-info">
              <div class="store-name">{{ store.store_name }}</div>
              <div class="store-platform">{{ store.platform }}</div>
            </div>
            <el-tag :type="store.is_active ? 'success' : 'info'">
              {{ store.is_active ? '已启用' : '已禁用' }}
            </el-tag>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage } from 'element-plus'

const dashboardData = ref({})

const fetchDashboard = async () => {
  try {
    const response = await axios.get('/api/dashboard')
    dashboardData.value = response.data
  } catch (error) {
    ElMessage.error('获取数据失败')
  }
}

const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    received: 'primary',
    restocked: 'success',
    refunded: 'success'
  }
  return types[status] || 'info'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('zh-CN')
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.dashboard {
  padding: 0;
}

.stat-card {
  margin-bottom: 20px;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: #fff;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  line-height: 1;
}

.stat-label {
  font-size: 14px;
  color: #999;
  margin-top: 8px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.store-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}

.store-item:last-child {
  border-bottom: none;
}

.store-info {
  flex: 1;
}

.store-name {
  font-weight: 500;
  color: #333;
}

.store-platform {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}
</style>
