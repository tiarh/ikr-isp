# API.md — IKR-ISP v1 Endpoints

Base URL: `https://ikr.sky.net.id/api/v1`
Auth: Bearer token (Sanctum)

## eBilling Sync (callback)

```
POST   /psb-orders/{id}/sync           Trigger fullSync manual
GET    /psb-orders/{id}/sync-status    Cek status sync terakhir
```

## FieldOps

```
GET    /odp-assets?lat=&lng=&radius=   List ODP assets (cached 5 min)
GET    /olt-assets?area_id=            List OLT assets
```

## OLT Provisioning

```
POST   /olt/provision
body: { psb_order_id, sn, port, onu_id?, name, password }
```

## Teknisi

```
GET    /teknisi                        List teknisi + open_ticket_count
```

## Web Routes (Inertia, requires session auth)

```
GET    /psb                            Dashboard
GET    /psb/orders                     List + filter
GET    /psb/orders/create              Form input
POST   /psb/orders                     Submit
GET    /psb/orders/{id}                Detail
GET    /psb/pipeline                   Kanban
GET    /psb/coverage                   Map + nearest ODP
POST   /psb/coverage/{id}/approve
POST   /psb/coverage/{id}/reject
GET    /psb/assignment
POST   /psb/assignment/{id}/assign
GET    /psb/provisioning
POST   /psb/provisioning/{id}/select-olt
POST   /psb/provisioning/{id}/provision
GET    /psb/orders/{id}/documents      Upload wizard
POST   /psb/orders/{id}/photo/{type}   6 photo types
POST   /psb/orders/{id}/measurements   Redaman + GPS
POST   /psb/orders/{id}/bai            TTD → PDF
POST   /psb/checklist/{id}/toggle      HiOS item
GET    /psb/orders/{id}/sync           Preview + sync
POST   /psb/orders/{id}/sync
GET    /psb/reports
GET    /psb/reports/export             XLSX
```
