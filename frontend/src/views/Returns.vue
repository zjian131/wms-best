<template>
  <div class="returns-container">
    <div class="page-header">
      <h2>退货管理</h2>
    </div>
    <el-card>
      <div class="search-bar">
        <el-select v-model="filters.status" placeholder="选择状态">
          <el-option label="全部" :value="''" />
          <el-option label="待处理" :value="pending" />
          <el-option label="已收货" :value="received" />
          <el-option label="已入库" :value="restocked" />
          <el-option label="已退款" :value="refunded" />
        </el-select>
        <el-button type="primary" @click="fetchReturns">搜索</el-button>
      </div>
      <el-table :data="returns" style="width: 100%">
        <el-table-column prop="return_number" label="退货单号" />
        <el-table-column prop="platform_return_id" label="平台单号" />
        <el-table-column prop="store.store_name" label="店铺" />
        <el-table-column prop="type" label="类型" />
        <el-table-column prop="status" label="状态">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="reason" label="退货原因" />
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
        <el-table-column label="操作">
          <template #default="{ row }">
            <el-button type="text" @click="viewDetail(row)">查看详情</el-button>
            <el-button v-if="row.status === 'pending'" type="text" @click="markReceived(row)">标记收货</el-button>
            <el-button v-if="row.status === 'received'" type="text" @click="markRestocked(row)">标记入库</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog :visible="showDetailModal" title="退货详情" @close="closeDetailModal">
      <div v-if="selectedReturn" class="return-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="退货单号">{{ selectedReturn.return_number }}</el-descriptions-item>
          <el-descriptions-item label="平台单号">{{ selectedReturn.platform_return_id }}</el-descriptions-item>
          <el-descriptions-item label="店铺">{{ selectedReturn.store?.store_name }}</el-descriptions-item>
          <el-descriptions-item label="类型">{{ selectedReturn.type }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="getStatusType(selectedReturn.status)">{{ getStatusText(selectedReturn.status) }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="退款金额">{{ selectedReturn.currency }} {{ selectedReturn.refund_amount }}</el-descriptions-item>
          <el-descriptions-item label="退货原因">{{ selectedReturn.reason }}</el-descriptions-item>
          <el-descriptions-item label="退货日期">{{ formatDate(selectedReturn.return_date) }}</el-descriptions-item>
          <el-descriptions-item label="追踪单号">{{ selectedReturn.tracking_number || '-' }}</el-descriptions-item>
          <el-descriptions-item label="物流公司">{{ selectedReturn.shipping_carrier || '-' }}</el-descriptions-item>
          <el-descriptions-item label="客户备注" :span="2">{{ selectedReturn.customer_note || '-' }}</el-descriptions-item>
        </el-descriptions>

        <div style="margin-top: 20px">
          <h4>退货商品</h4>
          <el-table :data="selectedReturn.items || []" style="width: 100%">
            <el-table-column prop="name" label="商品名称" />
            <el-table-column prop="sku" label="SKU" />
            <el-table-column prop="quantity" label="数量" />
            <el-table-column prop="price" label="单价" />
          </el-table>
        </div>

        <div style="margin-top: 20px">
          <el-form-item label="管理员备注">
            <el-input v-model="selectedReturn.admin_note" type="textarea" rows="3" />
          </el-form-item>
        </div>
      </div>
      <template #footer>
        <el-button @click="saveAdminNote">保存备注</el-button>
        <el-button @click="closeDetailModal">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage } from 'element-plus'

const returns = ref([])
const showDetailModal = ref(false)
const selectedReturn = ref(null)

const filters = reactive({
  status: '',
})

const fetchReturns = async () => {
  try {
    const params = new URLSearchParams()
    if (filters.status) {
      params.append('status', filters.status)
    }
    const response = await axios.get(`/api/returns?${params.toString()}`)
    returns.value = response.data.data
  } catch (error) {
    ElMessage.error('获取退货列表失败')
  }
}

const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    received: 'primary',
    restocked: 'success',
    refunded: 'success',
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    pending: '待处理',
    received: '已收货',
    restocked: '已入库',
    refunded: '已退款',
  }
  return texts[status] || status
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('zh-CN')
}

const viewDetail = async (row) => {
  try {
    const response = await axios.get(`/api/returns/${row.id}`)
    selectedReturn.value = response.data
    showDetailModal.value = true
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedReturn.value = null
}

const saveAdminNote = async () => {
  try {
    await axios.put(`/api/returns/${selectedReturn.value.id}`, {
      admin_note: selectedReturn.value.admin_note,
    })
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const markReceived = async (row) => {
  try {
    await axios.post(`/api/returns/${row.id}/mark-received`)
    ElMessage.success('标记成功')
    fetchReturns()
  } catch (error) {
    ElMessage.error('标记失败')
  }
}

const markRestocked = async (row) => {
  try {
    await axios.post(`/api/returns/${row.id}/mark-restocked`)
    ElMessage.success('标记成功')
    fetchReturns()
  } catch (error) {
    ElMessage.error('标记失败')
  }
}

onMounted(() => {
  fetchReturns()
})
</script>

<style scoped>
.returns-container {
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

.return-detail {
  padding: 10px;
}
</style>
