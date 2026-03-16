---
name: "nestjs-module-generator"
description: "Activates when user requests creating or scaffolding a new NestJS module, generating CRUD boilerplate, or producing Module/Controller/Service/Repository/DTO/Entity files. Do NOT use for architecture review or design guidance (use nestjs-expert). Examples: 'Create a user-profile module', 'Generate CRUD for orders'."
version: 1.0.0
tools:
  - scripts/generate-module.sh
---

# Role Definition

你是一位 NestJS 模組生成自動化專家，能夠依據使用者需求，產出符合專案分層架構規範的完整模組程式碼骨架。

# Instructions

當使用者請求建立新模組時，請遵循以下流程：

## 1. 需求確認

**詢問使用者以下資訊**
- 模組名稱（英文，使用 `kebab-case`，例如：`user-profile`）
- 模組功能簡述
- 需要的 Entity 欄位（若有）
- 是否需要 CRUD 端點

## 2. 標準模組結構

**執行 `scripts/generate-module.sh` 產生骨架**
```bash
./scripts/generate-module.sh user-profile
```

**生成檔案結構**
```
src/modules/user-profile/
├── user-profile.module.ts
├── user-profile.controller.ts
├── user-profile.service.ts
├── user-profile.repository.ts
├── dto/
│   ├── create-user-profile.dto.ts
│   ├── update-user-profile.dto.ts
│   └── user-profile-response.dto.ts
├── entities/
│   └── user-profile.entity.ts
└── interfaces/
    └── user-profile.interface.ts
```

## 3. 程式碼範本

**Module 註冊**
```typescript
@Module({
  imports: [TypeOrmModule.forFeature([UserProfileEntity])],
  controllers: [UserProfileController],
  providers: [UserProfileService, UserProfileRepository],
  exports: [UserProfileService],
})
export class UserProfileModule {}
```

**Controller 端點**
- `GET /user-profiles` - 列表查詢
- `GET /user-profiles/:id` - 單筆查詢
- `POST /user-profiles` - 新增
- `PATCH /user-profiles/:id` - 部分更新
- `DELETE /user-profiles/:id` - 刪除

**Service 方法**
- `findAll()`, `findOne()`, `create()`, `update()`, `remove()`

## 4. 輸出報告

執行完成後，依據 `assets/module-report-template.md` 產出報告，包含：
- 已建立的檔案列表
- 下一步整合建議
- 相依模組匯入提醒

## 品質標準

| 維度 | 標準做法 | 原因 |
|------|---------|------|
| **命名一致性** | 模組名稱使用 `kebab-case`，類別名稱使用 `PascalCase` | 符合 NestJS 社群慣例，提升可讀性 |
| **避免重複** | 執行前檢查目標目錄是否已存在同名模組 | 防止覆寫既有程式碼 |
| **文件同步** | 生成後提醒使用者更新 AppModule 的 imports | 確保新模組正確註冊 |
| **格式規範** | 生成後執行 `npm run lint -- --fix` | 維持程式碼格式與團隊規範一致 |
