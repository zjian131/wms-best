<template>
  <div class="warehouses-container">
    <div class="page-header">
      <h2>仓库管理</h2>
      <el-button type="primary" @click="openAddModal">
        <el-icon><Plus /></el-icon>
        添加仓库
      </el-button>
    </div>
    <el-card>
      <el-table :data="warehouses" style="width: 100%">
        <el-table-column prop="name" label="仓库名称" />
        <el-table-column prop="contact_person" label="联系人" />
        <el-table-column prop="phone" label="联系电话" />
        <el-table-column prop="country" label="国家" />
        <el-table-column prop="province" label="省份" />
        <el-table-column prop="city" label="城市" />
        <el-table-column prop="address" label="详细地址" />
        <el-table-column prop="is_default" label="默认">
          <template #default="{ row }">
            <el-tag v-if="row.is_default" type="success">默认</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作">
          <template #default="{ row }">
            <el-button v-if="!row.is_default" type="text" @click="setDefault(row)">设为默认</el-button>
            <el-button type="text" @click="openEditModal(row)">编辑</el-button>
            <el-button type="text" @click="deleteWarehouse(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog :visible="showModal" :title="isEdit ? '编辑仓库' : '添加仓库'" @close="closeModal">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="仓库名称" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="联系人">
          <el-input v-model="form.contact_person" />
        </el-form-item>
        <el-form-item label="联系电话">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="国家" prop="country">
          <el-input v-model="form.country" />
        </el-form-item>
        <el-form-item label="省份" prop="province">
          <el-input v-model="form.province" />
        </el-form-item>
        <el-form-item label="城市" prop="city">
          <el-input v-model="form.city" />
        </el-form-item>
        <el-form-item label="区县">
          <el-input v-model="form.district" />
        </el-form-item>
        <el-form-item label="详细地址" prop="address">
          <el-input v-model="form.address" type="textarea" rows="3" />
        </el-form-item>
        <el-form-item label="邮政编码">
          <el-input v-model="form.postal_code" />
        </el-form-item>
        <el-form-item>
          <el-switch v-model="form.is_default" />
          <span style="margin-left: 8px">设为默认仓库</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeModal">取消</el-button>
        <el-button type="primary" @click="saveWarehouse">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage, ElConfirm } from 'element-plus'

const warehouses = ref([])
const showModal = ref(false)
const isEdit = ref(false)
const formRef = ref(null)

const form = reactive({
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  country: '',
  province: '',
  city: '',
  district: '',
  address: '',
  postal_code: '',
  is_default: false,
})

const rules = {
  name: [{ required: true, message: '请输入仓库名称', trigger: 'blur' }],
  country: [{ required: true, message: '请输入国家', trigger: 'blur' }],
  province: [{ required: true, message: '请输入省份', trigger: 'blur' }],
  city: [{ required: true, message: '请输入城市', trigger: 'blur' }],
  address: [{ required: true, message: '请输入详细地址', trigger: 'blur' }],
}

const fetchWarehouses = async () => {
  try {
    const response = await axios.get('/api/warehouses')
    warehouses.value = response.data.data
  } catch (error) {
    ElMessage.error('获取仓库列表失败')
  }
}

const openAddModal = () => {
  isEdit.value = false
  Object.assign(form, {
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    country: '',
    province: '',
    city: '',
    district: '',
    address: '',
    postal_code: '',
    is_default: false,
  })
  showModal.value = true
}

const openEditModal = (row) => {
  isEdit.value = true
  Object.assign(form, {
    id: row.id,
    name: row.name,
    contact_person: row.contact_person,
    phone: row.phone,
    email: row.email,
    country: row.country,
    province: row.province,
    city: row.city,
    district: row.district,
    address: row.address,
    postal_code: row.postal_code,
    is_default: row.is_default,
  })
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  delete form.id
}

const saveWarehouse = async () => {
  try {
    await formRef.value.validate()
    if (isEdit.value) {
      await axios.put(`/api/warehouses/${form.id}`, form)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/warehouses', form)
      ElMessage.success('创建成功')
    }
    closeModal()
    fetchWarehouses()
  } catch (error) {
    ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
  }
}

const setDefault = async (row) => {
  try {
    await axios.post(`/api/warehouses/${row.id}/set-default`)
    ElMessage.success('设置成功')
    fetchWarehouses()
  } catch (error) {
    ElMessage.error('设置失败')
  }
}

const deleteWarehouse = async (row) => {
  if (row.is_default) {
    ElMessage.warning('不能删除默认仓库')
    return
  }
  await ElConfirm.confirm('确定要删除该仓库吗？', '提示', {
    type: 'warning',
  })
  try {
    await axios.delete(`/api/warehouses/${row.id}`)
    ElMessage.success('删除成功')
    fetchWarehouses()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

onMounted(() => {
  fetchWarehouses()
})
</script>

<style scoped>
.warehouses-container {
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
