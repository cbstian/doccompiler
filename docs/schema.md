# Schema Filament 5 - Lineamientos de Distribución de Componentes

## Resumen Ejecutivo

Este documento establece la arquitectura estándar para distribución de componentes en formularios de recursos Filament 5, siguiendo el patrón **tipo blog WordPress** donde:

- **Columna Principal (75%)**: Contenido editorial/principal (texto, descripciones, imágenes)
- **Sidebar (25%)**: Metadatos, estado, categorización, opciones avanzadas

---

## Estructura General de Grid en Filament 5

### Sistema de 12 Columnas

Filament 5 utiliza un sistema de grid de **12 columnas** similar a Bootstrap. Cada componente ocupa un número de columnas definido por `columnSpan()`.

```
┌─────────────────────────────────────────────────────────┐
│  Grid 12/12 (Contenedor Principal - 100% ancho)        │
├──────────────────────────────┬──────────────────────────┤
│                              │                          │
│  Grid 12/9 (Columna Primaria)│ Grid 12/3 (Sidebar)     │
│  (75% - Contenido Editorial) │ (25% - Metadatos)       │
│                              │                          │
│  • Información Principal     │ • Estado                │
│  • Descripciones            │ • Opciones Avanzadas    │
│  • Imágenes                 │ • Categorización        │
│  • Contenido                │ • Visibilidad           │
│                              │                          │
└──────────────────────────────┴──────────────────────────┘
```

---

## Estructura Estándar de Formulario

### Patrón Base

Todo formulario de recurso debe seguir esta estructura fundamental:

```php
Grid::make(12)                                    // Contenedor raíz (100%)
    ->columnSpanFull()
    ->components([
        
        Grid::make(12)                            // Columna principal
            ->columnSpan(9)                       // 9/12 = 75%
            ->components([
                // Secciones de contenido principal
                Section::make('Sección Principal 1')
                Section::make('Sección Principal 2')
                Section::make('Multimedia')
            ]),
        
        Grid::make(12)                            // Sidebar
            ->columnSpan(3)                       // 3/12 = 25%
            ->components([
                Section::make('Control')
            ]),
    ]);
```

### Asignación de Componentes por Ubicación

#### 🟦 Columna Principal (Grid 12/9)

**Criterios de inclusión:**
- Campos editoriales/de contenido principal
- Información descriptiva del recurso
- Campos de texto extenso (Textarea, RichEditor)
- Multimedia y galerías
- Datos que requieren mayor espacio visual

**Ejemplo de Secciones:**
- Contenido Principal / Información General
- Contenido Detallado / Descripciones
- Multimedia / Imágenes / Galería
- Precios / Valores Principales (si aplica)

#### 🟪 Sidebar (Grid 12/3)

**Criterios de inclusión:**
- Controles de publicación/estado
- Valores booleanos (Toggle)
- Categorizaciones rápidas
- Metadatos y opciones avanzadas
- Relaciones simples
- Información de control administrativo

**Ejemplo de Secciones:**
- Estado / Publicación
- Categorización / Tags
- Visibilidad
- Metadatos SEO (opcional)

---

## Paralelo con Arquitectura Editorial (WordPress)

Filament 5 replica el patrón de edición editorial de WordPress mediante una estructura intuitiva:

```
EDITOR EDITORIAL                   FILAMENT RESOURCE FORM
═══════════════════════════════════════════════════════════════
Editor de Contenido               → Grid 12/9 (Columna Principal)
├─ Título                         ├─ TextInput identificador
├─ Permalink/Slug                 ├─ TextInput slug
├─ Contenido                      ├─ Textarea/RichEditor
├─ Resumen                        ├─ Textarea corta
└─ Imagen Destacada               └─ SpatieMediaLibraryFileUpload

Controles Editoriales             → Grid 12/3 (Sidebar)
├─ Estado (Publicado/Borrador)    ├─ Toggle estado
├─ Destacado                      ├─ Toggle featured
├─ Categoría                      (Puede estar en sidebar o principal)
└─ Etiquetas                      (Según importancia)
```

---

## Ejemplo de Implementación

### BlogPostForm - Caso de Referencia

```php
<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->components([
                        // COLUMNA PRINCIPAL (75%)
                        Grid::make(12)
                            ->columnSpan(9)
                            ->components([
                                
                                // Editor de Contenido Principal
                                Section::make('Contenido del Post')
                                    ->columnSpanFull()
                                    ->components([
                                        TextInput::make('title')
                                            ->label('Título del Post')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($operation, $state, $set) {
                                                if ($operation === 'create') {
                                                    $set('slug', str($state)->slug());
                                                }
                                            }),
                                        
                                        TextInput::make('slug')
                                            ->label('Slug (Permalink)')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->alphaDash(),
                                        
                                        Textarea::make('excerpt')
                                            ->label('Extracto')
                                            ->rows(3)
                                            ->helperText('Resumen corto mostrado en listados'),
                                        
                                        Textarea::make('content')
                                            ->label('Contenido')
                                            ->rows(10)
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                                
                                // Imágenes/Multimedia
                                Section::make('Imágenes')
                                    ->columnSpanFull()
                                    ->components([
                                        SpatieMediaLibraryFileUpload::make('featured_image')
                                            ->label('Imagen Destacada')
                                            ->collection('featured_image')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->visibility('public')
                                            ->columnSpanFull(),
                                        
                                        SpatieMediaLibraryFileUpload::make('gallery')
                                            ->label('Galería de Imágenes')
                                            ->collection('gallery')
                                            ->multiple()
                                            ->reorderable()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxFiles(20)
                                            ->visibility('public')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        
                        // SIDEBAR (25%)
                        Grid::make(12)
                            ->columnSpan(3)
                            ->components([
                                
                                // Publicación
                                Section::make('Publicación')
                                    ->columnSpanFull()
                                    ->components([
                                        Toggle::make('is_published')
                                            ->label('Publicado')
                                            ->default(false),
                                        
                                        Toggle::make('is_featured')
                                            ->label('Post Destacado')
                                            ->default(false),
                                    ]),
                                
                                // Categorización
                                Section::make('Categorización')
                                    ->columnSpanFull()
                                    ->components([
                                        Select::make('category_id')
                                            ->label('Categoría')
                                            ->relationship('category', 'name')
                                            ->preload()
                                            ->searchable(),
                                        
                                        Select::make('tags')
                                            ->label('Etiquetas')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                    ]),
                                
                                // Metadatos SEO
                                Section::make('SEO')
                                    ->columnSpanFull()
                                    ->components([
                                        TextInput::make('meta_title')
                                            ->label('Título Meta')
                                            ->maxLength(60)
                                            ->helperText('Para motores de búsqueda'),
                                        
                                        Textarea::make('meta_description')
                                            ->label('Descripción Meta')
                                            ->rows(2)
                                            ->maxLength(160),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
```

---

## Reglas de Distribución (12 Columnas)

### Proporciones Comunes

| Tipo de Diseño | Columna Principal | Sidebar | Uso |
|---|---|---|---|
| **Blog Estándar** | 9/12 (75%) | 3/12 (25%) | Contenido normal + sidebar |
| **Full Width** | 12/12 (100%) | — | Página sin sidebar |
| **Dos Columnas Iguales** | 6/12 (50%) | 6/12 (50%) | Datos pareados |
| **Sidebar Ancho** | 8/12 (66%) | 4/12 (34%) | Más espacio para sidebar |
| **Contenido Dominante** | 10/12 (83%) | 2/12 (17%) | Sidebar muy pequeño |

---

## Atributos Clave de Grid

### `columnSpan(n)`
Define cuántas columnas ocupa un Grid dentro de su contenedor padre.

```php
Grid::make(12)->columnSpan(9)    // Ocupa 9 de 12 columnas = 75%
Grid::make(12)->columnSpan(3)    // Ocupa 3 de 12 columnas = 25%
```

### `columnSpanFull()`
Equivale a `columnSpan(12)` - ocupa todas las columnas disponibles.

### `columns(n)`
Define cuántas columnas internas tiene el Grid para sus componentes hijos.

```php
Grid::make(12)          // Grid con sistema interno de 12 columnas
    ->components([
        TextInput::make('foo')->columnSpan(6),  // Ocupa 6 de 12 = 50%
        TextInput::make('bar')->columnSpan(6),  // Ocupa 6 de 12 = 50%
    ])
```

---

## Variaciones de Layout Según Contexto

### Caso 1: Recurso Editorial (Blog, FAQ, Contenido)
```
┌─ Contenido Principal (75%)
│  ├─ Título, Slug, Extracto
│  ├─ Contenido Extenso
│  └─ Multimedia
├─ Controles (25%)
│  ├─ Publicado / Destacado
│  ├─ Categoría / Etiquetas
│  └─ Metadatos SEO
```

### Caso 2: Recurso Comercial (Producto, Servicio)
```
┌─ Información Principal (75%)
│  ├─ Nombre, Slug, SKU
│  ├─ Descripciones
│  ├─ Precios/Valores
│  └─ Multimedia
├─ Metadatos (25%)
│  ├─ Estado
│  ├─ Categorización
│  └─ Opciones Avanzadas
```

### Caso 3: Recurso de Configuración (Settings, Opciones)
```
┌─ Parámetros Principales (75%-100%)
│  ├─ Campos de configuración
│  ├─ Valores por defecto
│  └─ Descripciones
├─ Validaciones (25% - Opcional)
│  └─ Alcance, restricciones
```

---

## Decisión: ¿Cuándo Usar 75/25 vs. Otras Proporciones?

Evalúa estas preguntas para determinar la distribución:

1. **¿Hay muchos campos de contenido principal?**
   - Sí → Mantener 75/25 o aumentar a 80/20
   - No → Considerar 70/30 o 60/40

2. **¿Los controles son complejos?**
   - Sí → Ampliar sidebar a 4-5 columnas (70/30 o 66/34)
   - No → Mantener sidebar en 3 columnas (75/25)

3. **¿Es un formulario simple?**
   - Sí → Considerar 100/0 (full-width)
   - No → Distribuir según complejidad

4. **¿Hay mucha multimedia?**
   - Sí → 75/25 o más a favor de contenido (80/20)
   - No → Flexible según necesidad

---

## Mejores Prácticas

### ✅ DO (Hacer)

1. **Usar Grid anidados** para control de layout preciso
2. **Agrupar en Sections** por funcionalidad similar
3. **Mantener proporción 75/25** como estándar principal
4. **Colocar contenido editorial** en columna primaria
5. **Colocar controles/metadatos** en sidebar
6. **Usar `columnSpanFull()`** en Secciones importantes
7. **Validar campos** según importancia (más restricción en principales)
8. **Eager load en queries** (relaciones de Select/Relationship)
9. **Agrupar visualmente** campos relacionados en Sections
10. **Ordenar Sections** de arriba a abajo por importancia decreciente

### ❌ DON'T (No Hacer)

1. ❌ No usar más de 2 niveles de Grid anidación
2. ❌ No mezclar tipos de contenido sin agrupar en Sections
3. ❌ No violar la distribución 75/25 sin razón documentada
4. ❌ No colocar campos extensos (Textarea, RichEditor) en sidebar
5. ❌ No sobrecargar sidebar con demasiadas Sections
6. ❌ No dejar Grids vacíos o sin componentes
7. ❌ No usar `columnSpan()` arbitrariamente sin seguir patrón
8. ❌ No poner controles críticos únicamente en sidebar
9. ❌ No omitir validación en campos de contenido principal
10. ❌ No crear Sections sin un propósito o agrupación lógica

---

## Flujo de Construcción de un Recurso

### Paso a Paso

```
1. Crear Grid raíz (12)
   └─ ->columnSpanFull()
   
2. Definir Grid columna primaria
   └─ ->columnSpan(9)    // 75% del ancho
   
3. Agregar Sections en columna primaria
   ├─ Sección 1: Contenido Principal
   ├─ Sección 2: Contenido Detallado
   └─ Sección 3: Multimedia
   
4. Definir Grid sidebar
   └─ ->columnSpan(3)    // 25% del ancho
   
5. Agregar Sections en sidebar
   ├─ Sección Estado: Controles de Publicación
   ├─ Sección Categorización: Tags, Categorías
   └─ Sección Avanzado: Metadatos, SEO, Opciones
   
6. Validar distribución y proporciones
   ├─ Verificar que columna primaria tiene > 75% contenido
   ├─ Verificar que sidebar tiene controles simples
   └─ Asegurar balance visual
```

---

## Referencia Rápida: ComponentesFilament Comunes

| Component | Ubicación | Uso |
|-----------|-----------|-----|
| `TextInput` | Principal | Campos de texto simples |
| `Textarea` | Principal | Contenido multilínea |
| `RichEditor` | Principal | Editor WYSIWYG (si disponible) |
| `Select` | Ambos | Selecciones de lista |
| `Toggle` | Sidebar | Controles booleanos |
| `SpatieMediaLibraryFileUpload` | Principal | Gestión de media |
| `DatePicker` | Sidebar | Fechas y tiempos |
| `Section` | Ambos | Agrupadores visuales |

---

## Conclusión

Filament 5 proporciona un sistema de grid flexible que permite implementar el patrón **editorial estándar** (contenido + sidebar) de manera consistente mediante:

### Principios Fundamentales

1. **Distribuci\u00f3n 75/25 por defecto**
   - Columna primaria (Grid 12/9): Contenido y campos principales
   - Sidebar (Grid 12/3): Controles y metadatos

2. **Organización mediante Sections**
   - Agrupar campos por funcionalidad
   - Mantener consistencia visual
   - Facilitar navegación

3. **Componentes contextuales**
   - Seleccionar componente según tipo de dato
   - Colocar según importancia y espacio requerido
   - Validar según ubicación

4. **Escalabilidad**
   - Patrón adaptable a múltiples tipos de recursos
   - Proporciones ajustables según contexto
   - Mantenible por otros desarrolladores

### Aplicabilidad

Este patrón es efectivo para:
- Recursos editoriales (Blog, FAQ, Contenido)
- Recursos comerciales (Productos, Servicios)
- Recursos de configuración (Settings, Opciones)
- Cualquier formulario complejo que requiera jerarquía visual

La arquitectura es **escalable, mantenible y familiar** para usuarios de plataformas editoriales modernas.
