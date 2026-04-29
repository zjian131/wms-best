<template>
  <div class="products-container">
    <div class="page-header">
      <h2>商品管理</h2>
      <el-button type="primary" @click="openAddModal">
        <el-icon><Plus /></el-icon>
        添加商品
      </el-button>
    </div>
    <el-card>
      <div class="search-bar">
        <el-input v-model="searchText" placeholder="搜索商品名称或SKU" style="width: 300px" />
        <el-select v-model="filters.store_id" placeholder="选择店铺">
          <el-option label="全部" :value="''" />
          <el-option v-for="store in stores" :key="store.id" :label="store.store_name" :value="store.id" />
        </el-select>
        <el-button type="primary" @click="fetchProducts">搜索</el-button>
      </div>
      <el-table :data="products" style="width: 100%">
        <el-table-column prop="name" label="商品名称" />
        <el-table-column prop="sku" label="SKU" />
        <el-table-column prop="platform_product_id" label="平台商品ID" />
        <el-table-column prop="price" label="价格">
          <template #default="{ row }">
            {{ row.currency }} {{ row.price }}
          </template>
        </el-table-column>
        <el-table-column prop="stock_quantity" label="总库存" />
        <el-table-column prop="available_stock" label="可用库存" />
        <el-table-column prop="store.store_name" label="所属店铺" />
        <el-table-column prop="return_reason" label="退货原因" />
        <el-table-column label="操作">
          <template #default="{ row }">
            <el-button type="text" @click="openBindModal(row)">绑定订单</el-button>
            <el-button type="text" @click="openStockModal(row)">调整库存</el-button>
            <el-button type="text" @click="openEditModal(row)">编辑</el-button>
            <el-button type="text" @click="deleteProduct(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog :visible="showModal" :title="modalTitle" @close="closeModal">
      <el-form :model="form" :rules="formRules" ref="formRef" label-width="120px">
        <el-form-item label="店铺" prop="store_id">
          <el-select v-model="form.store_id">
            <el-option v-for="store in stores" :key="store.id" :label="store.store_name" :value="store.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="平台商品ID" prop="platform_product_id">
          <el-input v-model="form.platform_product_id" />
        </el-form-item>
        <el-form-item label="SKU" prop="sku">
          <el-input v-model="form.sku" />
        </el-form-item>
        <el-form-item label="商品名称" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="价格">
          <el-input v-model="form.price" type="number" />
        </el-form-item>
        <el-form-item label="库存数量">
          <el-input v-model="form.stock_quantity" type="number" />
        </el-form-item>
        <el-form-item label="退货原因">
          <el-input v-model="form.return_reason" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeModal">取消</el-button>
        <el-button type="primary" @click="saveProduct">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog :visible="showBindModal" title="绑定订单" @close="closeBindModal">
      <el-form :model="bindForm" label-width="120px">
        <el-form-item label="目标订单">
          <el-select v-model="bindForm.order_id">
            <el-option v-for="order in availableOrders" :key="order.id" :label="order.order_number" :value="order.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="数量">
          <el-input v-model="bindForm.quantity" type="number" :max="selectedProduct?.available_stock" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeBindModal">取消</el-button>
        <el-button type="primary" @click="bindProduct">绑定</el-button>
      </template>
    </el-dialog>

    <el-dialog :visible="showStockModal" title="调整库存" @close="closeStockModal">
      <el-form :model="stockForm" label-width="120px">
        <el-form-item label="总库存">
          <el-input v-model="stockForm.stock_quantity" type="number" />
        </el-form-item>
        <el-form-item label="可用库存">
          <el-input v-model="stockForm.available_stock" type="number" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeStockModal">取消</el-button>
        <el-button type="primary" @click="updateStock">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage, ElConfirm } from 'element-plus'

const products = ref([])
const stores = ref([])
const searchText = ref('')
const filters = reactive({
  store_id: '',
})

const showModal = ref(false)
const showBindModal = ref(false)
const showStockModal = ref(false)
const isEdit = ref(false)
const formRef = ref(null)
const modalTitle = ref('')

const selectedProduct = ref(null)

const form = reactive({
  store_id: '',
  platform_product_id: '',
  sku: '',
  name: '',
  price: 0,
  stock_quantity: 0,
  return_reason: '',
})

const bindForm = reactive({
  order_id: '',
  quantity: 1,
})

const stockForm = reactive({
  stock_quantity: 0,
  available_stock: 0,
})

const formRules = {
  store_id: [{ required: true, message: '请选择店铺', trigger: 'change' }],
  platform_product_id: [{ required: true, message: '请输入平台商品ID', trigger: 'blur' }],
  sku: [{ required: true, message: '请输入SKU', trigger: 'blur' }],
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
}

const availableOrders = ref([])

const fetchProducts = async () => {
  try {
    const params = new URLSearchParams()
    if (searchText.value) {
      params.append('name', searchText.value)
      params.append('sku', searchText.value)
    }
    if (filters.store_id) {
      params.append('store_id', filters.store_id)
    }
    const response = await axios.get(`/api/products?${params.toString()}`)
    products.value = response.data.data
  } catch (error) {
    ElMessage.error('获取商品列表失败')
  }
}

const fetchStores = async () => {
  try {
    const response = await axios.get('/api/stores')
    stores.value = response.data.data
  } catch (error) {
    console.error('获取店铺列表失败')
  }
}

const openAddModal = () => {
  isEdit.value = false
  modalTitle.value = '添加商品'
  Object.assign(form, {
    store_id: '',
    platform_product_id: '',
    sku: '',
    name: '',
    price: 0,
    stock_quantity: 0,
    return_reason: '',
  })
  showModal.value = true
}

const openEditModal = (row) => {
  isEdit.value = true
  modalTitle.value = '编辑商品'
  Object.assign(form, {
    id: row.id,
    store_id: row.store_id,
    platform_product_id: row.platform_product_id,
    sku: row.sku,
    name: row.name,
    price: row.price,
    stock_quantity: row.stock_quantity,
    return_reason: row.return_reason,
  })
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  delete form.id
}

const saveProduct = async () => {
  try {
    if (isEdit.value) {
      await axios.put(`/api/products/${form.id}`, form)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/products', form)
      ElMessage.success('创建成功')
    }
    closeModal()
    fetchProducts()
  } catch (error) {
    ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
  }
}

const openBindModal = async (row) => {
  selectedProduct.value = row
  bindForm.order_id = ''
  bindForm.quantity = 1
  try {
    const response = await axios.get('/api/orders')
    availableOrders.value = response.data.data
  } catch (error) {
    console.error('获取订单失败')
  }
  showBindModal.value = true
}

const closeBindModal = () => {
  showBindModal.value = false
  selectedProduct.value = null
}

const bindProduct = async () => {
  try {
    await axios.post(`/api/products/${selectedProduct.value.id}/bind-order`, {
      order_id: bindForm.order_id,
      quantity: bindForm.quantity,
    })
    ElMessage.success('绑定成功')
    closeBindModal()
    fetchProducts()
  } catch (error) {
    ElMessage.error('绑定失败')
  }
}

const openStockModal = (row) => {
  selectedProduct.value = row
  stockForm.stock_quantity = row.stock_quantity
  stockForm.available_stock = row.available_stock
  showStockModal.value = true
}

const closeStockModal = () => {
  showStockModal.value = false
  selectedProduct.value = null
}

const updateStock = async () => {
  try {
    await axios.post(`/api/products/${selectedProduct.value.id}/update-stock`, stockForm)
    ElMessage.success('库存更新成功')
    closeStockModal()
    fetchProducts()
  } catch (error) {
    ElMessage.error('更新失败')
  }
}

const deleteProduct = async (row) => {
  await ElConfirm.confirm('确定要删除该商品吗？', '提示', {
    type: 'warning',
  })
  try {
    await axios.delete(`/api/products/${row.id}`)
    ElMessage.success('删除成功')
    fetchProducts()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  fetchProducts()
  fetchStores()
})
</script>

<style scoped>
.products-container {
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
</style>
