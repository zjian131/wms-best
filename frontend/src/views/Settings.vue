<template>
  <div class="settings-container">
    <div class="page-header">
      <h2>系统设置</h2>
    </div>
    <el-card>
      <el-tabs type="border-card">
        <el-tab-pane label="个人信息">
          <el-form :model="profileForm" label-width="120px">
            <el-form-item label="姓名">
              <el-input v-model="profileForm.name" />
            </el-form-item>
            <el-form-item label="邮箱">
              <el-input v-model="profileForm.email" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="saveProfile">保存</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
        <el-tab-pane label="密码修改">
          <el-form :model="passwordForm" :rules="passwordRules" ref="passwordFormRef" label-width="120px">
            <el-form-item label="原密码" prop="old_password">
              <el-input v-model="passwordForm.old_password" type="password" />
            </el-form-item>
            <el-form-item label="新密码" prop="new_password">
              <el-input v-model="passwordForm.new_password" type="password" />
            </el-form-item>
            <el-form-item label="确认密码" prop="confirm_password">
              <el-input v-model="passwordForm.confirm_password" type="password" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="changePassword">修改密码</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
        <el-tab-pane label="系统信息">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="系统版本">1.0.0</el-descriptions-item>
            <el-descriptions-item label="PHP版本">{{ phpVersion }}</el-descriptions-item>
            <el-descriptions-item label="数据库">MySQL</el-descriptions-item>
            <el-descriptions-item label="安装时间">{{ installTime }}</el-descriptions-item>
          </el-descriptions>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { ElMessage } from 'element-plus'

const profileForm = reactive({
  name: '',
  email: '',
})

const passwordForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})

const passwordFormRef = ref(null)

const passwordRules = {
  old_password: [{ required: true, message: '请输入原密码', trigger: 'blur' }],
  new_password: [{ required: true, message: '请输入新密码', trigger: 'blur' }, { min: 8, message: '密码至少8位', trigger: 'blur' }],
  confirm_password: [{ required: true, message: '请确认密码', trigger: 'blur' }],
}

const phpVersion = ref('')
const installTime = ref('')

const fetchProfile = async () => {
  try {
    const response = await axios.get('/api/auth/me')
    profileForm.name = response.data.name
    profileForm.email = response.data.email
  } catch (error) {
    console.error('获取用户信息失败')
  }
}

const saveProfile = async () => {
  try {
    await axios.put('/api/auth/profile', profileForm)
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const changePassword = async () => {
  try {
    await passwordFormRef.value.validate()
    if (passwordForm.new_password !== passwordForm.confirm_password) {
      ElMessage.error('两次输入的密码不一致')
      return
    }
    await axios.put('/api/auth/profile', { password: passwordForm.new_password })
    ElMessage.success('密码修改成功')
    passwordForm.old_password = ''
    passwordForm.new_password = ''
    passwordForm.confirm_password = ''
  } catch (error) {
    ElMessage.error('修改失败')
  }
}

onMounted(() => {
  fetchProfile()
  phpVersion.value = '8.2+'
  installTime.value = '2024-01-01 00:00:00'
})
</script>

<style scoped>
.settings-container {
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
