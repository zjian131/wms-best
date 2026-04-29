<template>
  <div class="stores-container">
    <div class="page-header">
      <h2>店铺管理</h2>
      <el-button type="primary" @click="openAddModal">
        <el-icon><Plus /></el-icon>
        添加店铺
      </el-button>
    </div>
    <el-card>
      <el-table :data="stores" style="width: 100%">
        <el-table-column prop="store_name" label="店铺名称" />
        <el-table-column prop="platform" label="平台" />
        <el-table-column prop="store_id" label="平台店铺ID" />
        <el-table-column prop="is_active" label="状态">
          <template #default="{ row }">
            <el-switch :value="row.is_active" @change="toggleStatus(row)" />
          </template>
        </el-table-column>
        <el-table-column prop="last_sync_at" label="最后同步">
          <template #default="{ row }">
            {{ row.last_sync_at ? formatDate(row.last_sync_at) : '从未同步' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作">
          <template #default="{ row }">
            <el-button type="text" @click="syncStore(row)">
              <el-icon><RefreshCw /></el-icon>
              同步
            </el-button>
            <el-button type="text" @click="openEditModal(row)">
              <el-icon><Edit /></el-icon>
              编辑
            </el-button>
            <el-button type="text" @click="deleteStore(row)">
              <el-icon><Delete /></el-icon>
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog :visible="showModal" :title="isEdit ? '编辑店铺' : '添加店铺'" @close="closeModal">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="平台" prop="platform">
          <el-select v-model="form.platform">
            <el-option v-for="(platform, key) in platforms" :key="key" :label="platform.name" :value="key" />
          </el-select>
        </el-form-item>
        <el-form-item label="店铺名称" prop="store_name">
          <el-input v-model="form.store_name" />
        </el-form-item>
        <el-form-item label="平台店铺ID" prop="store_id">
          <el-input v-model="form.store_id" />
        </el-form-item>
        <el-form-item label="Access Token">
          <el-input v-model="form.access_token" type="textarea" rows="3" />
        </el-form-item>
        <el-form-item label="Refresh Token">
          <el-input v-model="form.refresh_token" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeModal">取消</el-button>
        <el-button type="primary" @click="saveStore">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage, ElConfirm } from 'element-plus'

const stores = ref([])
const showModal = ref(false)
const isEdit = ref(false)
const formRef = ref(null)
const syncing = ref(false)

const platforms = ref({})

const form = reactive({
  platform: '',
  store_name: '',
  store_id: '',
  access_token: '',
  refresh_token: '',
})

const rules = {
  platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
  store_name: [{ required: true, message: '请输入店铺名称', trigger: 'blur' }],
  store_id: [{ required: true, message: '请输入平台店铺ID', trigger: 'blur' }],
}

const fetchStores = async () => {
  try {
    const response = await axios.get('/api/stores')
    stores.value = response.data.data
  } catch (error) {
    ElMessage.error('获取店铺列表失败')
  }
}

const fetchPlatforms = async () => {
  try {
    const response = await axios.get('/api/platforms/list')
    platforms.value = response.data
  } catch (error) {
    console.error('获取平台列表失败')
  }
}

const openAddModal = () => {
  isEdit.value = false
  Object.assign(form, {
    platform: '',
    store_name: '',
    store_id: '',
    access_token: '',
    refresh_token: '',
  })
  showModal.value = true
}

const openEditModal = (row) => {
  isEdit.value = true
  Object.assign(form, {
    platform: row.platform,
    store_name: row.store_name,
    store_id: row.store_id,
    access_token: row.access_token || '',
    refresh_token: row.refresh_token || '',
  })
  form.id = row.id
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  delete form.id
}

const saveStore = async () => {
  try {
    await formRef.value.validate()
    if (isEdit.value) {
      await axios.put(`/api/stores/${form.id}`, form)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/stores', form)
      ElMessage.success('创建成功')
    }
    closeModal()
    fetchStores()
  } catch (error) {
    ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
  }
}

const toggleStatus = async (row) => {
  try {
    await axios.put(`/api/stores/${row.id}`, { is_active: !row.is_active })
    row.is_active = !row.is_active
    ElMessage.success('状态更新成功')
  } catch (error) {
    row.is_active = !row.is_active
    ElMessage.error('更新失败')
  }
}

const syncStore = async (row) => {
  syncing.value = true
  try {
    await axios.post(`/api/stores/${row.id}/sync`)
    ElMessage.success('同步成功')
    fetchStores()
  } catch (error) {
    ElMessage.error('同步失败')
  } finally {
    syncing.value = false
  }
}

const deleteStore = async (row) => {
  await ElConfirm.confirm('确定要删除该店铺吗？', '提示', {
    type: 'warning',
  })
  try {
    await axios.delete(`/api/stores/${row.id}`)
    ElMessage.success('删除成功')
    fetchStores()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('zh-CN')
}

onMounted(() => {
  fetchStores()
  fetchPlatforms()
})
</script>

<style scoped>
.stores-container {
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
</style>
