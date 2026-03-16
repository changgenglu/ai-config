# NestJS 命名規範

此文件定義專案中的命名規範，確保程式碼風格一致性。

---

## 總覽

| 類型 | 命名規範 | 範例 |
|------|----------|------|
| Class / Interface | PascalCase | `UserService`, `IUserRepository` |
| Method / Variable | camelCase | `findById`, `userName` |
| Constant | UPPER_SNAKE_CASE | `MAX_RETRY_COUNT` |
| File Name | kebab-case | `user-profile.service.ts` |
| DB Column | snake_case | `created_at`, `user_id` |

---

## 檔案命名

### 模式
```
{feature-name}.{type}.ts
```

### 類型對照

| 類型 | 後綴 | 範例 |
|------|------|------|
| Module | `.module.ts` | `user.module.ts` |
| Controller | `.controller.ts` | `user.controller.ts` |
| Service | `.service.ts` | `user.service.ts` |
| Repository | `.repository.ts` | `user.repository.ts` |
| Entity | `.entity.ts` | `user.entity.ts` |
| DTO | `.dto.ts` | `create-user.dto.ts` |
| Interface | `.interface.ts` | `user.interface.ts` |
| Guard | `.guard.ts` | `jwt-auth.guard.ts` |
| Interceptor | `.interceptor.ts` | `logging.interceptor.ts` |
| Filter | `.filter.ts` | `http-exception.filter.ts` |
| Pipe | `.pipe.ts` | `validation.pipe.ts` |
| Gateway | `.gateway.ts` | `game.gateway.ts` |
| Enum | `.enum.ts` | `order-status.enum.ts` |
| Constant | `.constant.ts` | `error-code.constant.ts` |

---

## 類別命名

### Controller
```typescript
// 檔案: user.controller.ts
@Controller('users')
export class UserController {}
```

### Service
```typescript
// 檔案: user.service.ts
@Injectable()
export class UserService {}
```

### Repository
```typescript
// 檔案: user.repository.ts
@Injectable()
export class UserRepository extends Repository<UserEntity> {}
```

### Entity
```typescript
// 檔案: user.entity.ts
@Entity('users')
export class UserEntity {}  // 或 User
```

### DTO
```typescript
// 檔案: create-user.dto.ts
export class CreateUserDto {}

// 檔案: update-user.dto.ts
export class UpdateUserDto extends PartialType(CreateUserDto) {}

// 檔案: user-response.dto.ts
export class UserResponseDto {}
```

### Interface
```typescript
// 檔案: user.interface.ts
export interface IUser {}
export interface IUserService {}
```

---

## 方法命名

### CRUD 方法

| 操作 | 方法名稱 | 回傳類型 |
|------|----------|----------|
| 查詢列表 | `findAll()` | `Entity[]` |
| 查詢單筆 | `findOne()` / `findById()` | `Entity \| null` |
| 查詢或拋錯 | `findOneOrFail()` | `Entity` |
| 建立 | `create()` | `Entity` |
| 更新 | `update()` | `Entity` |
| 刪除 | `remove()` / `delete()` | `void` |
| 軟刪除 | `softRemove()` | `void` |

### 業務方法

採用動詞開頭的 camelCase：

```typescript
// Service
async processPayment(orderId: string): Promise<Payment> {}
async validateUser(token: string): Promise<boolean> {}
async sendNotification(userId: string, message: string): Promise<void> {}

// Repository
async findByEmail(email: string): Promise<User | null> {}
async findActiveUsers(): Promise<User[]> {}
async countByStatus(status: OrderStatus): Promise<number> {}
```

---

## 常數命名

### 錯誤碼
```typescript
// 檔案: error-code.constant.ts
export const ERROR_CODE = {
  USER_NOT_FOUND: 'USER_NOT_FOUND',
  INVALID_TOKEN: 'INVALID_TOKEN',
  INSUFFICIENT_BALANCE: 'INSUFFICIENT_BALANCE',
} as const;
```

### 設定值
```typescript
// 檔案: config.constant.ts
export const MAX_RETRY_COUNT = 3;
export const DEFAULT_PAGE_SIZE = 20;
export const CACHE_TTL_SECONDS = 3600;
```

---

## 資料庫相關

### Table 名稱
- 使用 `snake_case`
- 使用複數形式

```typescript
@Entity('user_profiles')  // ✅
@Entity('UserProfile')   // ❌
```

### Column 名稱
- 使用 `snake_case`
- 明確指定 `name` 屬性

```typescript
@Column({ name: 'user_id' })
userId: string;

@Column({ name: 'created_at' })
createdAt: Date;
```

### 外鍵命名
格式: `fk_{table}_{reference_table}_{column}`

```sql
fk_orders_users_user_id
```

### 索引命名
格式: `idx_{table}_{columns}`

```typescript
@Index('idx_users_email')
@Column({ name: 'email' })
email: string;
```

---

## 環境變數

- 使用 `UPPER_SNAKE_CASE`
- 加上前綴區分類別

```bash
# Database
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=secret
DB_DATABASE=myapp

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=secret

# JWT
JWT_SECRET=your-secret-key
JWT_EXPIRES_IN=1d

# External Services
WALLET_TCP_HOST=10.0.0.1
WALLET_TCP_PORT=9000
```

---

## 禁止事項

### ❌ 縮寫 (除非廣泛認知)

```typescript
// ❌ 錯誤
const usr = await userService.findOne(id);
const pwd = data.password;

// ✅ 正確
const user = await userService.findOne(id);
const password = data.password;
```

### ❌ 匈牙利命名法

```typescript
// ❌ 錯誤
const strName: string;
const iCount: number;
const bIsActive: boolean;

// ✅ 正確
const name: string;
const count: number;
const isActive: boolean;
```

### ❌ 無意義的命名

```typescript
// ❌ 錯誤
const data = await service.getData();
const result = await service.process();

// ✅ 正確
const users = await userService.findAll();
const order = await orderService.create(dto);
```
