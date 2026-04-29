<template>
  <div class="orders-container">
    <div class="page-header">
      <h2>订单管理</h2>
    </div>
    <el-card>
      <div class="search-bar">
        <el-input v-model="searchText" placeholder="搜索订单号" style="width: 300px" />
        <el-button type="primary" @click="fetchOrders">搜索</el-button>
      </div>
      <el-table :data="orders" style="width: 100%">
        <el-table-column prop="order_number" label="订单号" />
        <el-table-column prop="platform_order_id" label="平台订单ID" />
        <el-table-column prop="store.store_name" label="店铺" />
        <el-table-column prop="customer_name" label="客户名称" />
        <el-table-column prop="total_amount" label="订单金额">
          <template #default="{ row }">
            {{ row.currency }} {{ row.total_amount }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="order_date" label="下单时间">
          <template #default="{ row }">
            {{ formatDate(row.order_date) }}
          </template>
        </el-table-column>
        <el-table-column label="操作">
          <template #default="{ row }">
            <el-button type="text" @click="viewDetail(row)">查看详情</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog :visible="showDetailModal" title="订单详情" @close="closeDetailModal">
      <div v-if="selectedOrder" class="order-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="订单号">{{ selectedOrder.order_number }}</el-descriptions-item>
          <el-descriptions-item label="平台订单ID">{{ selectedOrder.platform_order_id }}</el-descriptions-item>
          <el-descriptions-item label="店铺">{{ selectedOrder.store?.store_name }}</el-descriptions-item>
          <el-descriptions-item label="客户名称">{{ selectedOrder.customer_name }}</el-descriptions-item>
          <el-descriptions-item label="客户邮箱">{{ selectedOrder.customer_email }}</el-descriptions-item>
          <el-descriptions-item label="客户电话">{{ selectedOrder.customer_phone }}</el-descriptions-item>
          <el-descriptions-item label="订单金额">{{ selectedOrder.currency }} {{ selectedOrder.total_amount }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="getStatusType(selectedOrder.status)">{{ selectedOrder.status }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="下单时间">{{ formatDate(selectedOrder.order_date) }}</el-descriptions-item>
          <el-descriptions-item label="付款时间">{{ formatDate(selectedOrder.payment_date) }}</el-descriptions-item>
        </el-descriptions>

        <div style="margin-top: 20px">
          <h4>订单商品</h4>
          <el-table :data="selectedOrder.items || []" style="width: 100%">
            <el-table-column prop="name" label="商品名称" />
            <el-table-column prop="sku" label="SKU" />
            <el-table-column prop="quantity" label="数量" />
            <el-table-column prop="price" label="单价" />
          </el-table>
        </div>
      </div>
      <template #footer>
        <el-button @click="closeDetailModal">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage } from 'element-plus'

const orders = ref([])
const showDetailModal = ref(false)
const selectedOrder = ref(null)
const searchText = ref('')

const fetchOrders = async () => {
  try {
    const params = new URLSearchParams()
    if (searchText.value) {
      params.append('order_number', searchText.value)
    }
    const response = await axios.get(`/api/orders?${params.toString()}`)
    orders.value = response.data.data
  } catch (error) {
    ElMessage.error('获取订单列表失败')
  }
}

const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    paid: 'primary',
    shipped: 'info',
    completed: 'success',
    cancelled: 'danger',
  }
  return types[status] || 'info'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('zh-CN')
}

const viewDetail = async (row) => {
  try {
    const response = await axios.get(`/api/orders/${row.id}`)
    selectedOrder.value = response.data
    showDetailModal.value = true
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedOrder.value = null
}

onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
.orders-container {
  padding: 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0;
}

.search-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  align-items: center;
}

.order-detail {
  padding: 10px;
}
</style>
