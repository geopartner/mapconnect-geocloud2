# settings.getColumns Usage Map

A PostgreSQL function used throughout the codebase to retrieve table and raster column metadata. It accepts two parameters: one for geometry columns filters and one for raster columns filters.

---

## Usage by Function & Context

### 1. WFS Service - Capabilities Generation
**File:** `app/wfs/handlers/GetCapabilities.php::handle()` (Line 256)
- **When:** When a WFS `GetCapabilities` request is received
- **Purpose:** Builds the WFS XML capabilities document listing all published tables and layers
- **Query:** Filters for `enableows=true` only
- **Triggered by:** WFS client requesting service capabilities

**File:** `app/wfs/handlers/DescribeFeatureType.php::handle()` (Lines 101, 141)
- **When:** When a WFS `DescribeFeatureType` request is received
- **Purpose:** Returns XML schema describing a specific feature type's attributes
  - Line 101: Gets full table/layer definition
  - Line 141: Gets geometry column details for type mapping
- **Triggered by:** WFS client requesting feature type schema

---

### 2. Layer/Database Management API
**File:** `app/models/Layer.php::getAll()` (Lines 108, 182, 188, 195, 200, 577)
- **When:** API calls to fetch available layers/tables
- **Purpose:** Main metadata aggregation for dashboard/API consumers
- **Filters:** Supports multiple query strategies:
  - Line 108: Single layer lookup by schema.table.geometry
  - Line 182: Schema-based filtering (multiple schemas)
  - Line 188: Specific layer queries (schema.table)
  - Line 195: Tag-based filtering (tags ? 'value')
  - Line 200: No filter (all tables)
- **Additional logic:** Caches results, applies authentication rules, enriches with extent/versioning data
- **Triggered by:** Dashboard/API requests for layer listings

**File:** `app/models/Database.php::renameSchema()` (Line 340)
- **When:** Admin renames a schema
- **Purpose:** Fetches all tables in schema to update their geometry_columns_join records
- **Triggered by:** Admin schema rename operation

**File:** `app/models/Table.php::getTableStructure()` (Lines 247, 249)
- **When:** API fetches table structure/metadata
- **Purpose:** Gets all tables for schema or all tables in database
  - Line 247: Scoped by schema
  - Line 249: All tables (1=1 condition)
- **Triggered by:** Dashboard requesting table list/structure

---

### 3. Mapfile Generation
**File:** `app/models/Mapfile.php::getOwsLayerRows()` (Line 60)
- **When:** WMS/WFS mapfile is being generated
- **Purpose:** Retrieves only OWS-enabled layers for map rendering
- **Called by:** WMS handlers building mapserver configuration
- **Triggered by:** Map tile/feature requests

---

### 4. Map Cache Configuration
**File:** `app/controllers/Mapcachefile.php` (Line 162)
- **When:** Mapcache configuration XML is being generated
- **Purpose:** Gets all publishable tables/rasters for tile caching setup
- **Filters:** Optional schema inclusion list from config
- **Triggered by:** Tile service initialization or config generation

---

### 5. Access Control & Authentication
**File:** `app/inc/BasicAuth.php::authenticate()` (Line 70)

#### Entry Points (Where authenticate is called)

**A. WMS/WFS Service Requests** - `app/controllers/Wms.php` (Lines 79, 103, 114)
- **When:** Every WMS/WFS GET or POST request arrives
- **Frequency:** **VERY FREQUENT** - on every non-trusted IP request
- **Conditions:** Only if source IP is not in trusted list
- **Method calls:** Via `Controller::basicHttpAuthLayer()`
  - GET requests: Checks all layers in `LAYERS`/`TYPENAMES` parameters
  - POST requests: Checks each typeName in WFS Query elements
- **Purpose:** Pre-validates layer access before processing request
- **Triggered by:** WMS client (map requests, GetMap, GetFeatureInfo) or WFS client (GetFeature)

**B. WFS Transaction Operations** - `app/wfs/handlers/Transaction.php` (Lines 130, 258, 455)
- **When:** WFS feature modification requests (Insert/Update/Delete)
- **Frequency:** Per modified feature in transaction
- **Conditions:** Only if source is not trusted AND layer requires auth
- **Operations:**
  - Line 130: INSERT operation
  - Line 258: UPDATE operation  
  - Line 455: DELETE operation
- **Purpose:** Validates write access before executing data modification
- **Triggered by:** WFS Transaction request with Insert/Update/Replace/Delete operations

#### What gets queried
**The `settings.getColumns` call inside authenticate:**
- Retrieves the `privileges` JSON field for the specific table
- Validates user's group/username against privilege levels
- Checks permission levels: "none", "read", "write", "read/write"

#### Performance Impact
- Called **on every non-trusted request** to every layer
- One `settings.getColumns` query **per layer per request** (GET) or per layer per operation (Transaction)
- Results are cached by Layer model but authenticate runs independently

---

## Call Flow Hierarchy

```
API/Client Requests
├── WMS/WFS Requests (GET/POST) - Controller::dispatch()
│   ├── [FREQUENT] Wms.php
│   │   └── basicHttpAuthLayer() (if IP not trusted)
│   │       └── BasicAuth::authenticate() [settingsColumns called]
│   │           ├── GetMap requests
│   │           ├── GetFeature requests  
│   │           ├── GetFeatureInfo requests
│   │           └── WFS Query POST requests
│   │
├── WFS Transaction Requests - Transaction::handle()
│   ├── [PER FEATURE] Insert operation (Line 130)
│   │   └── BasicAuth::authenticate() (if IP not trusted & auth required)
│   ├── [PER FEATURE] Update operation (Line 258)
│   │   └── BasicAuth::authenticate() (if IP not trusted & auth required)
│   └── [PER FEATURE] Delete operation (Line 455)
│       └── BasicAuth::authenticate() (if IP not trusted & auth required)
│
├── WFS Capabilities Request - GetCapabilities::handle() [Line 256]
│   └── settings.getColumns() [retrieves all OWS-enabled tables]
│
├── GET /api/layer - Layer::getAll() [Lines 108, 182-200, 577]
│   └── settings.getColumns() [multiple strategies]
│
├── GET /api/table - Table::getTableStructure() [Lines 247, 249]
│   └── settings.getColumns()
│
├── Admin Operations
│   ├── Rename schema → Database::renameSchema() [Line 340]
│   └── Mapcache config → Mapcachefile [Line 162]
│
└── Map Rendering
    └── Mapfile::getOwsLayerRows() [Line 60]
```

**Legend:**
- `[FREQUENT]` = Called on every non-trusted request
- `[PER FEATURE]` = Called for each modified feature in transaction

---

## Summary Statistics
- **Files:** 12 across codebase
- **Direct invocations:** 20+ calls
- **Actual runtime frequency:** 🔴 **HIGH** - `authenticate()` runs on every non-trusted request

### Most Frequent Usage Path:
**WMS/WFS per-request authentication** (via Wms.php)
- Called on **every GET/POST request** if source IP is not trusted
- Can call `settings.getColumns` multiple times per request (once per layer)
- Example: A WMS GetMap with 5 layers = 5 authenticate() calls = 5 queries

### Primary Use Cases by Runtime Frequency:
1. **Per-request authentication** (very frequent) - WMS/WFS requests
   - Security checkpoint on every non-trusted client request
2. **Transaction-level authentication** (frequent during edits) - WFS Insert/Update/Delete
3. **Metadata retrieval** for dashboard/API (moderate) - Layer::getAll()
4. **OWS service generation** (moderate) - WFS/WMS handlers
5. **Schema/database operations** (rare) - Admin functions

