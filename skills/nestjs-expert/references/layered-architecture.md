# NestJS 分層架構規範

此文件定義專案的分層架構 (Layered Architecture) 規範與各層職責。

---

## 架構總覽

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│                    (Controllers, DTOs)                       │
├─────────────────────────────────────────────────────────────┤
│                    Application Layer                         │
│                    (Services, Use Cases)                     │
├─────────────────────────────────────────────────────────────┤
│                    Domain Layer                              │
│                    (Entities, Interfaces)                    │
├─────────────────────────────────────────────────────────────┤
│                   Infrastructure Layer                       │
│           (Repositories, External Services, Cache)          │
└─────────────────────────────────────────────────────────────┘
```

---

## 各層職責

### 1. Presentation Layer (展示層)

**元件**: Controller, Gateway, DTO

**職責**:
- 處理 HTTP 請求 / WebSocket 事件
- 請求參數驗證 (使用 class-validator)
- 回應格式轉換
- HTTP 狀態碼處理

**規範**:
- ❌ 不得包含商業邏輯
- ❌ 不得直接存取 Repository
- ✅ 僅負責轉發請求至 Service
- ✅ 使用 DTO 進行資料傳輸

**程式碼範例**:
```typescript
@Controller('users')
export class UserController {
  constructor(private readonly userService: UserService) {}

  @Get(':id')
  async findOne(@Param('id', ParseUUIDPipe) id: string): Promise<UserResponseDto> {
    const user = await this.userService.findOne(id);
    return plainToInstance(UserResponseDto, user);
  }
}
```

---

### 2. Application Layer (應用層)

**元件**: Service

**職責**:
- 實作商業邏輯
- 協調多個 Repository 操作
- 管理交易範圍 (Transaction Scope)
- 呼叫外部服務

**規範**:
- ✅ 單一職責原則 (一個 Service 專注一個領域)
- ✅ 可注入多個 Repository
- ✅ 處理例外並轉換為適當的 HTTP 例外
- ❌ 不得包含 HTTP 相關邏輯

**程式碼範例**:
```typescript
@Injectable()
export class OrderService {
  constructor(
    private readonly orderRepository: OrderRepository,
    private readonly userRepository: UserRepository,
    private readonly walletService: WalletService,
  ) {}

  async createOrder(userId: string, dto: CreateOrderDto): Promise<Order> {
    // 1. 驗證使用者
    const user = await this.userRepository.findOneOrFail(userId);
    
    // 2. 檢查餘額
    const balance = await this.walletService.getBalance(userId);
    if (balance < dto.amount) {
      throw new BadRequestException('餘額不足');
    }
    
    // 3. 建立訂單
    const order = this.orderRepository.create({
      userId,
      amount: dto.amount,
      status: OrderStatus.PENDING,
    });
    
    return this.orderRepository.save(order);
  }
}
```

---

### 3. Domain Layer (領域層)

**元件**: Entity, Interface, Enum, Value Object

**職責**:
- 定義核心業務實體
- 定義介面契約
- 業務規則封裝

**規範**:
- ✅ 純 TypeScript 類別
- ✅ 與框架無關 (可在 Entity 使用 TypeORM 裝飾器例外)
- ❌ 不得依賴 Application 或 Infrastructure 層

**程式碼範例**:
```typescript
// entities/order.entity.ts
@Entity('orders')
export class OrderEntity {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  amount: number;

  @Column({ type: 'enum', enum: OrderStatus, default: OrderStatus.PENDING })
  status: OrderStatus;

  // 業務方法
  canCancel(): boolean {
    return this.status === OrderStatus.PENDING;
  }
}

// interfaces/wallet.interface.ts
export interface IWalletService {
  getBalance(userId: string): Promise<number>;
  debit(userId: string, amount: number): Promise<void>;
  credit(userId: string, amount: number): Promise<void>;
}
```

---

### 4. Infrastructure Layer (基礎設施層)

**元件**: Repository, External Service Adapter, Cache Manager

**職責**:
- 資料存取實作
- 外部服務整合
- 快取操作

**規範**:
- ✅ 實作 Domain 層定義的介面
- ✅ 封裝第三方套件細節
- ❌ 不得包含商業邏輯

**程式碼範例**:
```typescript
// repositories/order.repository.ts
@Injectable()
export class OrderRepository extends Repository<OrderEntity> {
  constructor(private dataSource: DataSource) {
    super(OrderEntity, dataSource.createEntityManager());
  }

  async findByUserIdWithPagination(
    userId: string,
    page: number,
    limit: number,
  ): Promise<[OrderEntity[], number]> {
    return this.createQueryBuilder('order')
      .where('order.userId = :userId', { userId })
      .orderBy('order.createdAt', 'DESC')
      .skip((page - 1) * limit)
      .take(limit)
      .getManyAndCount();
  }
}

// services/redis-cache.service.ts
@Injectable()
export class RedisCacheService {
  constructor(@Inject('REDIS_CLIENT') private redis: Redis) {}

  async get<T>(key: string): Promise<T | null> {
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async set(key: string, value: any, ttl: number): Promise<void> {
    await this.redis.setex(key, ttl, JSON.stringify(value));
  }
}
```

---

## 模組目錄結構

```
src/modules/order/
├── order.module.ts              # 模組定義 (wiring)
├── order.controller.ts          # Presentation Layer
├── order.service.ts             # Application Layer
├── order.repository.ts          # Infrastructure Layer
├── dto/
│   ├── create-order.dto.ts      # Presentation Layer
│   └── order-response.dto.ts    # Presentation Layer
├── entities/
│   └── order.entity.ts          # Domain Layer
├── interfaces/
│   └── order.interface.ts       # Domain Layer
└── enums/
    └── order-status.enum.ts     # Domain Layer
```

---

## 依賴規則

```
Presentation → Application → Domain ← Infrastructure
                    ↓               ↗
             Infrastructure ────────
```

**規則**:
1. 上層可依賴下層
2. 同層之間透過介面溝通
3. 下層不得依賴上層
4. Infrastructure 實作 Domain 定義的介面

---

## 常見錯誤

### ❌ Controller 直接使用 Repository

```typescript
// 錯誤
@Controller('users')
export class UserController {
  constructor(private readonly userRepository: UserRepository) {}
  
  @Get(':id')
  findOne(@Param('id') id: string) {
    return this.userRepository.findOne({ where: { id } });
  }
}
```

### ✅ Controller 透過 Service 存取

```typescript
// 正確
@Controller('users')
export class UserController {
  constructor(private readonly userService: UserService) {}
  
  @Get(':id')
  findOne(@Param('id') id: string) {
    return this.userService.findOne(id);
  }
}
```

### ❌ Service 處理 HTTP 相關邏輯

```typescript
// 錯誤
@Injectable()
export class UserService {
  findOne(id: string): Response {
    const user = this.repository.findOne({ where: { id } });
    return {
      statusCode: 200,  // HTTP 狀態碼不應出現在 Service
      body: user,
    };
  }
}
```

### ✅ Service 只回傳資料

```typescript
// 正確
@Injectable()
export class UserService {
  async findOne(id: string): Promise<User> {
    const user = await this.repository.findOne({ where: { id } });
    if (!user) {
      throw new NotFoundException(`User #${id} not found`);
    }
    return user;
  }
}
```
