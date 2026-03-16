#!/bin/bash

# NestJS 模組生成腳本
# 使用方式: ./generate-module.sh <module-name>
# 範例: ./generate-module.sh user-profile

set -e

# 檢查參數
if [ -z "$1" ]; then
  echo "❌ 錯誤：請提供模組名稱"
  echo "使用方式: ./generate-module.sh <module-name>"
  exit 1
fi

MODULE_NAME=$1
MODULE_DIR="src/modules/${MODULE_NAME}"

# 轉換命名格式
# kebab-case -> PascalCase
to_pascal_case() {
  echo "$1" | sed -r 's/(^|-)(\w)/\U\2/g'
}

# kebab-case -> camelCase
to_camel_case() {
  local pascal=$(to_pascal_case "$1")
  echo "${pascal,}"
}

PASCAL_NAME=$(to_pascal_case "$MODULE_NAME")
CAMEL_NAME=$(to_camel_case "$MODULE_NAME")

# 檢查目錄是否已存在
if [ -d "$MODULE_DIR" ]; then
  echo "❌ 錯誤：模組目錄已存在：${MODULE_DIR}"
  exit 1
fi

echo "🚀 開始建立模組：${MODULE_NAME}"

# 建立目錄結構
mkdir -p "${MODULE_DIR}/dto"
mkdir -p "${MODULE_DIR}/entities"
mkdir -p "${MODULE_DIR}/interfaces"

# 生成 Module 檔案
cat > "${MODULE_DIR}/${MODULE_NAME}.module.ts" << EOF
import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ${PASCAL_NAME}Controller } from './${MODULE_NAME}.controller';
import { ${PASCAL_NAME}Service } from './${MODULE_NAME}.service';
import { ${PASCAL_NAME}Repository } from './${MODULE_NAME}.repository';
import { ${PASCAL_NAME}Entity } from './entities/${MODULE_NAME}.entity';

@Module({
  imports: [TypeOrmModule.forFeature([${PASCAL_NAME}Entity])],
  controllers: [${PASCAL_NAME}Controller],
  providers: [${PASCAL_NAME}Service, ${PASCAL_NAME}Repository],
  exports: [${PASCAL_NAME}Service],
})
export class ${PASCAL_NAME}Module {}
EOF

# 生成 Controller 檔案
cat > "${MODULE_DIR}/${MODULE_NAME}.controller.ts" << EOF
import {
  Controller,
  Get,
  Post,
  Patch,
  Delete,
  Body,
  Param,
  Query,
  ParseUUIDPipe,
} from '@nestjs/common';
import { ${PASCAL_NAME}Service } from './${MODULE_NAME}.service';
import { Create${PASCAL_NAME}Dto } from './dto/create-${MODULE_NAME}.dto';
import { Update${PASCAL_NAME}Dto } from './dto/update-${MODULE_NAME}.dto';

@Controller('${MODULE_NAME}s')
export class ${PASCAL_NAME}Controller {
  constructor(private readonly ${CAMEL_NAME}Service: ${PASCAL_NAME}Service) {}

  @Get()
  findAll(@Query() query: any) {
    return this.${CAMEL_NAME}Service.findAll(query);
  }

  @Get(':id')
  findOne(@Param('id', ParseUUIDPipe) id: string) {
    return this.${CAMEL_NAME}Service.findOne(id);
  }

  @Post()
  create(@Body() dto: Create${PASCAL_NAME}Dto) {
    return this.${CAMEL_NAME}Service.create(dto);
  }

  @Patch(':id')
  update(
    @Param('id', ParseUUIDPipe) id: string,
    @Body() dto: Update${PASCAL_NAME}Dto,
  ) {
    return this.${CAMEL_NAME}Service.update(id, dto);
  }

  @Delete(':id')
  remove(@Param('id', ParseUUIDPipe) id: string) {
    return this.${CAMEL_NAME}Service.remove(id);
  }
}
EOF

# 生成 Service 檔案
cat > "${MODULE_DIR}/${MODULE_NAME}.service.ts" << EOF
import { Injectable, NotFoundException } from '@nestjs/common';
import { ${PASCAL_NAME}Repository } from './${MODULE_NAME}.repository';
import { Create${PASCAL_NAME}Dto } from './dto/create-${MODULE_NAME}.dto';
import { Update${PASCAL_NAME}Dto } from './dto/update-${MODULE_NAME}.dto';
import { ${PASCAL_NAME}Entity } from './entities/${MODULE_NAME}.entity';

@Injectable()
export class ${PASCAL_NAME}Service {
  constructor(private readonly ${CAMEL_NAME}Repository: ${PASCAL_NAME}Repository) {}

  async findAll(query: any): Promise<${PASCAL_NAME}Entity[]> {
    return this.${CAMEL_NAME}Repository.find();
  }

  async findOne(id: string): Promise<${PASCAL_NAME}Entity> {
    const entity = await this.${CAMEL_NAME}Repository.findOne({ where: { id } });
    if (!entity) {
      throw new NotFoundException(\`${PASCAL_NAME} #\${id} not found\`);
    }
    return entity;
  }

  async create(dto: Create${PASCAL_NAME}Dto): Promise<${PASCAL_NAME}Entity> {
    const entity = this.${CAMEL_NAME}Repository.create(dto);
    return this.${CAMEL_NAME}Repository.save(entity);
  }

  async update(id: string, dto: Update${PASCAL_NAME}Dto): Promise<${PASCAL_NAME}Entity> {
    const entity = await this.findOne(id);
    Object.assign(entity, dto);
    return this.${CAMEL_NAME}Repository.save(entity);
  }

  async remove(id: string): Promise<void> {
    const entity = await this.findOne(id);
    await this.${CAMEL_NAME}Repository.softRemove(entity);
  }
}
EOF

# 生成 Repository 檔案
cat > "${MODULE_DIR}/${MODULE_NAME}.repository.ts" << EOF
import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { ${PASCAL_NAME}Entity } from './entities/${MODULE_NAME}.entity';

@Injectable()
export class ${PASCAL_NAME}Repository extends Repository<${PASCAL_NAME}Entity> {
  constructor(private dataSource: DataSource) {
    super(${PASCAL_NAME}Entity, dataSource.createEntityManager());
  }

  // 在此新增自定義查詢方法
}
EOF

# 生成 Entity 檔案
cat > "${MODULE_DIR}/entities/${MODULE_NAME}.entity.ts" << EOF
import {
  Entity,
  Column,
  PrimaryGeneratedColumn,
  CreateDateColumn,
  UpdateDateColumn,
  DeleteDateColumn,
} from 'typeorm';

@Entity('${MODULE_NAME//-/_}s')
export class ${PASCAL_NAME}Entity {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  // 在此新增欄位定義

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  @DeleteDateColumn({ name: 'deleted_at' })
  deletedAt: Date;
}
EOF

# 生成 DTO 檔案
cat > "${MODULE_DIR}/dto/create-${MODULE_NAME}.dto.ts" << EOF
import { IsString, IsOptional } from 'class-validator';

export class Create${PASCAL_NAME}Dto {
  // 在此定義建立時的欄位
}
EOF

cat > "${MODULE_DIR}/dto/update-${MODULE_NAME}.dto.ts" << EOF
import { PartialType } from '@nestjs/mapped-types';
import { Create${PASCAL_NAME}Dto } from './create-${MODULE_NAME}.dto';

export class Update${PASCAL_NAME}Dto extends PartialType(Create${PASCAL_NAME}Dto) {}
EOF

cat > "${MODULE_DIR}/dto/${MODULE_NAME}-response.dto.ts" << EOF
export class ${PASCAL_NAME}ResponseDto {
  id: string;
  createdAt: Date;
  updatedAt: Date;
}
EOF

# 生成 Interface 檔案
cat > "${MODULE_DIR}/interfaces/${MODULE_NAME}.interface.ts" << EOF
export interface I${PASCAL_NAME} {
  id: string;
  createdAt: Date;
  updatedAt: Date;
}
EOF

echo ""
echo "✅ 模組建立完成！"
echo ""
echo "📂 已建立檔案："
find "${MODULE_DIR}" -type f | sort
echo ""
echo "📌 下一步："
echo "1. 編輯 Entity 新增欄位定義"
echo "2. 更新 DTO 對應欄位"
echo "3. 在 AppModule 匯入 ${PASCAL_NAME}Module"
echo "4. 執行 npm run migration:generate 產生遷移檔案"
