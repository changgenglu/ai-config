## 📝 模組生成報告

- **模組名稱**: {{moduleName}}
- **生成時間**: {{timestamp}}
- **生成狀態**: {{status}}

---

## 📂 已建立檔案

| 檔案路徑 | 說明 |
|----------|------|
| `{{modulePath}}/{{moduleName}}.module.ts` | 模組定義檔 |
| `{{modulePath}}/{{moduleName}}.controller.ts` | 控制器 |
| `{{modulePath}}/{{moduleName}}.service.ts` | 服務層 |
| `{{modulePath}}/{{moduleName}}.repository.ts` | 資料存取層 |
| `{{modulePath}}/entities/{{moduleName}}.entity.ts` | TypeORM 實體 |
| `{{modulePath}}/dto/create-{{moduleName}}.dto.ts` | 建立 DTO |
| `{{modulePath}}/dto/update-{{moduleName}}.dto.ts` | 更新 DTO |
| `{{modulePath}}/dto/{{moduleName}}-response.dto.ts` | 回應 DTO |
| `{{modulePath}}/interfaces/{{moduleName}}.interface.ts` | 介面定義 |

---

## 🔗 整合步驟

### 1. 匯入模組至 AppModule

```typescript
// src/app.module.ts
import { {{pascalName}}Module } from './modules/{{moduleName}}/{{moduleName}}.module';

@Module({
  imports: [
    // ... 其他模組
    {{pascalName}}Module,
  ],
})
export class AppModule {}
```

### 2. 編輯 Entity 欄位

請至 `entities/{{moduleName}}.entity.ts` 新增必要欄位：

```typescript
@Column({ type: 'varchar', length: 100, comment: '欄位說明' })
fieldName: string;
```

### 3. 同步更新 DTO

確保 `dto/create-{{moduleName}}.dto.ts` 包含對應的驗證器。

### 4. 產生 Migration

```bash
npm run migration:generate -- -n Create{{pascalName}}Table
npm run migration:run
```

---

## ⚠️ 注意事項

{{#if warnings}}
{{#each warnings}}
- {{this}}
{{/each}}
{{else}}
- 無特殊警告
{{/if}}
