import { db, uploadFile, getTempFileURL } from './cloud.js'
import env from '../env.js'
import { generateUniqueShortId, isValidShortId } from './shortId.js'

export async function getMenus() {
  try {
    const c = db().collection(env.cloud.collections.dish)
    const r = await c.where({}).get()
    const raw = r?.data || []
    const items = raw.map(x => ({
      _id: x._id || x.id,
      name: x.name || '',
      desc: x.description || '',
      price: Number(x.price) || 0,
      tag: x.is_favorite ? '宝宝最爱' : (x.is_new ? '新品' : (x.is_recommend ? '今日推荐' : '')),
      imageUrl: x.cover_image || '',
      eta: Number(x.cook_time) || 0,
      published: x.published !== false,
      catId: x.category_id || null
    }))
    const ids = items.map(x => x.imageUrl).filter(v => v && !/^https?:\/\//.test(v))
    if (ids.length) {
      const res = await getTempFileURL(ids)
      const map = new Map()
      ;(res?.fileList || []).forEach(f => map.set(f.fileID, f.tempFileURL))
      items.forEach(x => { if (x.imageUrl && map.has(x.imageUrl)) x.imageUrl = map.get(x.imageUrl) })
    }
    return items
  } catch (e) {
    return []
  }
}

export async function getMenusWithTags() {
  const database = db()
  try {
    const c = database.collection(env.cloud.collections.dish)
    const r = await c.where({}).get()
    const raw = r?.data || []
    const items = raw.map(x => ({
      _id: x._id || x.id,
      name: x.name || '',
      desc: x.description || '',
      price: Number(x.price) || 0,
      tag: x.is_favorite ? '宝宝最爱' : (x.is_new ? '新品' : (x.is_recommend ? '今日推荐' : '')),
      imageUrl: x.cover_image || '',
      eta: Number(x.cook_time) || 0,
      published: x.published !== false,
      catId: x.category_id || null,
      tags: []
    }))
    const fileIds = items.map(x => x.imageUrl).filter(v => v && !/^https?:\/\//.test(v))
    if (fileIds.length) {
      const res = await getTempFileURL(fileIds)
      const map = new Map()
      ;(res?.fileList || []).forEach(f => map.set(f.fileID, f.tempFileURL))
      items.forEach(x => { if (x.imageUrl && map.has(x.imageUrl)) x.imageUrl = map.get(x.imageUrl) })
    }
    const dishIds = items.map(x => x._id).filter(Boolean)
    if (!dishIds.length) return items
    const _ = database.command
    const relC = database.collection(env.cloud.collections.dishTagRelation)
    const relR = await relC.where({ dish_id: _.in(dishIds) }).get()
    const rels = relR?.data || []
    const tagIds = Array.from(new Set(rels.map(x => x.tag_id).filter(Boolean)))
    let tagMap = new Map()
    if (tagIds.length) {
      const tagC = database.collection(env.cloud.collections.dishTag)
      const tagR = await tagC.where({ _id: _.in(tagIds) }).get()
      const tags = tagR?.data || []
      tagMap = new Map(tags.map(t => [t._id || t.id, t.name]))
    }
    const dishTagNames = new Map()
    rels.forEach(r => {
      const did = r.dish_id
      const tid = r.tag_id
      const name = tagMap.get(tid)
      if (!name) return
      const arr = dishTagNames.get(did) || []
      arr.push(name)
      dishTagNames.set(did, arr)
    })
    items.forEach(x => { x.tags = dishTagNames.get(x._id) || [] })
    return items
  } catch (e) {
    return []
  }
}

export async function getHomeMenus(limit = 4) {
  const list = await getMenusWithTags()
  const arr = (list || [])
    .filter(x => x.published !== false)
    .sort((a, b) => {
      const sa = (a.tag === '宝宝最爱' ? 3 : a.tag === '推荐' || a.tag === '今日推荐' ? 2 : a.tag === '新品' ? 1 : 0)
      const sb = (b.tag === '宝宝最爱' ? 3 : b.tag === '推荐' || b.tag === '今日推荐' ? 2 : b.tag === '新品' ? 1 : 0)
      return sb - sa
    })
    .slice(0, limit)
  return arr
}

export async function getRecentOrderSummaries(limit = 2) {
  const database = db()
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  const feedC = database.collection(env.cloud.collections.feedingRecord)
  try {
    const or = await orderC.where({}).get()
    const orders = (or?.data || []).sort((a, b) => (b.created_at || 0) - (a.created_at || 0)).slice(0, limit)
    const ids = orders.map(o => o._id || o.id).filter(Boolean)
    if (!ids.length) return []
    const _ = database.command
    const ir = await itemC.where({ order_id: _.in(ids) }).get()
    const items = ir?.data || []
    const fr = await feedC.where({ order_id: _.in(ids) }).get()
    const feeds = fr?.data || []
    const itemMap = new Map()
    items.forEach(it => {
      const arr = itemMap.get(it.order_id) || []
      arr.push(it)
      itemMap.set(it.order_id, arr)
    })
    const feedMap = new Map()
    feeds.forEach(f => feedMap.set(f.order_id, f))
    const res = orders.map(o => {
      const arr = itemMap.get(o._id || o.id) || []
      const names = arr.map(x => x.dish_name).filter(Boolean)
      const comment = (feedMap.get(o._id || o.id)?.comment) || (o.note || '')
      return { names, created_at: o.created_at || 0, comment }
    })
    return res
  } catch (e) { return [] }
}

function currentUserId() {
  try { const v = uni.getStorageSync('user_id'); if (v) return v } catch(e) {}
  return 1
}

export async function getFavorites() {
  const database = db()
  const uid = currentUserId()
  try {
    const favC = database.collection(env.cloud.collections.favoriteDish)
    const fr = await favC.where({ user_id: uid }).get()
    const favs = fr?.data || []
    const dishIds = favs.map(f => f.dish_id).filter(Boolean)
    if (!dishIds.length) return []
    const _ = database.command
    const dishC = database.collection(env.cloud.collections.dish)
    const dr = await dishC.where({ _id: _.in(dishIds) }).get()
    const dishes = dr?.data || []
    const map = new Map(dishes.map(d => [d._id || d.id, d]))
    return favs.map(f => {
      const d = map.get(f.dish_id) || {}
      return {
        _id: d._id || d.id,
        name: d.name || '',
        desc: d.description || '',
        price: Number(d.price) || 0,
        qty: Number(f.quantity) || 1,
        note: ''
      }
    })
  } catch(e) { return [] }
}

export async function addFavorite(dishId, delta = 1) {
  const database = db()
  const uid = currentUserId()
  const favC = database.collection(env.cloud.collections.favoriteDish)
  try {
    const r = await favC.where({ user_id: uid, dish_id: dishId }).get()
    const list = r?.data || []
    let qty = 0
    if (list.length) {
      const it = list[0]
      const nid = it._id || it.id
      qty = Number(it.quantity || 0) + (Number(delta) || 1)
      await favC.doc(nid).update({ data: { quantity: qty, updated_at: Date.now()/1000 } })
    } else {
      qty = Number(delta) || 1
      await favC.add({ data: { user_id: uid, dish_id: dishId, quantity: qty, created_at: Date.now()/1000, updated_at: Date.now()/1000 } })
    }
    const all = await favC.where({ user_id: uid }).get()
    const total = (all?.data || []).reduce((s, i) => s + (Number(i.quantity) || 0), 0)
    return { qty, total }
  } catch(e) { return { qty: 0, total: Number(uni.getStorageSync('fav_count') || 0) || 0 } }
}

export async function updateFavoriteQty(dishId, qty) {
  const database = db()
  const uid = currentUserId()
  const favC = database.collection(env.cloud.collections.favoriteDish)
  try {
    const r = await favC.where({ user_id: uid, dish_id: dishId }).get()
    const list = r?.data || []
    if (!list.length) return 0
    const it = list[0]
    const nid = it._id || it.id
    await favC.doc(nid).update({ data: { quantity: Number(qty) || 1, updated_at: Date.now()/1000 } })
    return qty
  } catch(e) { return 0 }
}

export async function deleteFavorite(dishId) {
  const database = db()
  const uid = currentUserId()
  const favC = database.collection(env.cloud.collections.favoriteDish)
  try {
    const r = await favC.where({ user_id: uid, dish_id: dishId }).get()
    const list = r?.data || []
    if (!list.length) return false
    const it = list[0]
    const nid = it._id || it.id
    await favC.doc(nid).remove()
    return true
  } catch(e) { return false }
}

export async function updateMenuItem(id, payload) {
  const c = db().collection(env.cloud.collections.dish)
  const data = {
    name: payload.name,
    price: Number(payload.price) || 0,
    description: payload.desc ?? payload.chefNote ?? '',
    cover_image: payload.imageUrl || '',
    cook_time: Number(payload.eta) || 0,
    is_favorite: payload.tag === '宝宝最爱',
    is_new: payload.tag === '新品',
    is_recommend: payload.tag === '今日推荐',
    published: payload.published !== false,
    supply: Number(payload.supply) || 0,
    chefNote: payload.chefNote || '',
    category_id: payload.catId ?? null
  }
  return c.doc(id).update({ data })
}

export async function addMenuItem(payload) {
  const c = db().collection(env.cloud.collections.dish)
  const data = {
    name: payload.name,
    price: Number(payload.price) || 0,
    description: payload.desc ?? payload.chefNote ?? '',
    cover_image: payload.imageUrl || '',
    cook_time: Number(payload.eta) || 0,
    is_favorite: payload.tag === '宝宝最爱',
    is_new: payload.tag === '新品',
    is_recommend: payload.tag === '今日推荐',
    published: payload.published !== false,
    supply: Number(payload.supply) || 0,
    chefNote: payload.chefNote || '',
    category_id: payload.catId ?? null
  }
  const r = await c.add({ data })
  return r?._id || ''
}

export async function uploadImage(localPath) {
  const ext = (localPath.split('.').pop() || 'jpg').toLowerCase()
  const name = `${env.cloud.storage.menuImagesDir}/${Date.now()}-${Math.random().toString(16).slice(2)}.${ext}`
  const r = await uploadFile(name, localPath)
  return r?.fileID || ''
}

export async function deleteMenuItem(id) {
  if (!id) throw new Error('缺少菜品ID')
  const c = db().collection(env.cloud.collections.dish)
  return c.doc(id).remove()
}

export async function deleteDishRelations(dishId) {
  if (!dishId) throw new Error('缺少菜品ID')
  const c = db().collection(env.cloud.collections.dishTagRelation)
  return c.where({ dish_id: dishId }).remove()
}

export async function getCategories() {
  try {
    const c = db().collection(env.cloud.collections.dishCategory)
    const r = await c.where({}).get()
    const list = (r?.data || []).sort((a, b) => (a.sort || 0) - (b.sort || 0))
    return list
  } catch (e) { return [] }
}

export async function addCategory(name) {
  if (!name || !name.trim()) throw new Error('分类名称不能为空')
  const c = db().collection(env.cloud.collections.dishCategory)
  const r = await c.add({ data: { name: name.trim(), sort: 0, created_at: Date.now()/1000, updated_at: Date.now()/1000 } })
  return r?._id || ''
}

export async function updateCategory(id, name) {
  if (!id) throw new Error('缺少分类ID')
  if (!name || !name.trim()) throw new Error('分类名称不能为空')
  const c = db().collection(env.cloud.collections.dishCategory)
  return c.doc(id).update({ data: { name: name.trim(), updated_at: Date.now()/1000 } })
}

export async function deleteCategory(id) {
  if (!id) throw new Error('缺少分类ID')
  const c = db().collection(env.cloud.collections.dishCategory)
  return c.doc(id).remove()
}

export async function getTags() {
  try {
    const c = db().collection(env.cloud.collections.dishTag)
    const r = await c.where({}).get()
    return r?.data || []
  } catch (e) { return [] }
}

export async function addTag(name) {
  if (!name || !name.trim()) throw new Error('标签名称不能为空')
  const c = db().collection(env.cloud.collections.dishTag)
  const r = await c.add({ data: { name: name.trim(), created_at: Date.now()/1000, updated_at: Date.now()/1000 } })
  return r?._id || ''
}

export async function updateTag(id, name) {
  if (!id) throw new Error('缺少标签ID')
  if (!name || !name.trim()) throw new Error('标签名称不能为空')
  const c = db().collection(env.cloud.collections.dishTag)
  return c.doc(id).update({ data: { name: name.trim(), updated_at: Date.now()/1000 } })
}

export async function deleteTag(id) {
  if (!id) throw new Error('缺少标签ID')
  const c = db().collection(env.cloud.collections.dishTag)
  return c.doc(id).remove()
}

export async function getDishTags(dishId) {
  if (!dishId) return []
  const c = db().collection(env.cloud.collections.dishTagRelation)
  const r = await c.where({ dish_id: dishId }).get()
  return r?.data || []
}

export async function setDishTags(dishId, tagIds = []) {
  if (!dishId) throw new Error('缺少菜品ID')
  const c = db().collection(env.cloud.collections.dishTagRelation)
  await c.where({ dish_id: dishId }).remove()
  if (!Array.isArray(tagIds) || !tagIds.length) return
  const tasks = tagIds.map(tid => c.add({ data: { dish_id: dishId, tag_id: tid, created_at: Date.now()/1000, updated_at: Date.now()/1000 } }))
  await Promise.all(tasks)
}

export async function getUserOrders(limit = 20) {
  const database = db()
  const uid = currentUserId()
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  const feedC = database.collection(env.cloud.collections.feedingRecord)
  try {
    const or = await orderC.where({ user_id: uid }).get()
    const orders = (or?.data || []).sort((a, b) => (b.created_at || 0) - (a.created_at || 0)).slice(0, limit)
    const ids = orders.map(o => o._id || o.id).filter(Boolean)
    if (!ids.length) return []
    const _ = database.command
    const ir = await itemC.where({ order_id: _.in(ids) }).get()
    const items = ir?.data || []
    const fr = await feedC.where({ order_id: _.in(ids) }).get()
    const feeds = fr?.data || []
    const itemMap = new Map()
    items.forEach(it => {
      const arr = itemMap.get(it.order_id) || []
      arr.push({ name: it.dish_name || '', qty: Number(it.quantity) || 1, price: Number(it.price) || 0, dish_id: it.dish_id })
      itemMap.set(it.order_id, arr)
    })
    const feedMap = new Map()
    feeds.forEach(f => feedMap.set(f.order_id, f))
    return orders.map(o => ({
      _id: o._id || o.id,
      cook_id: o.cook_id,
      status: Number(o.status) || 1,
      created_at: Number(o.created_at) || 0,
      items: itemMap.get(o._id || o.id) || [],
      note: (feedMap.get(o._id || o.id)?.comment) || (o.note || '')
    }))
  } catch (e) { return [] }
}

export function orderStatusText(s) {
  const v = Number(s) || 1
  if (v === 1) return '待制作'
  if (v === 2) return '制作中'
  if (v === 3) return '已完成'
  if (v === 4) return '已取消'
  return '待制作'
}

export async function getDishDetail(dishId) {
  if (!dishId) return null
  const database = db()
  const dishC = database.collection(env.cloud.collections.dish)
  const dr = await dishC.where({ _id: dishId }).get()
  const d = (dr?.data || [])[0]
  if (!d) return null
  const it = {
    _id: d._id || d.id,
    name: d.name || '',
    desc: d.description || '',
    price: Number(d.price) || 0,
    tag: d.is_favorite ? '宝宝最爱' : (d.is_new ? '新品' : (d.is_recommend ? '今日推荐' : '')),
    imageUrl: d.cover_image || '',
    catId: d.category_id || null,
    chefNote: d.chefNote || '',
    avgScore: Number(d.avg_score) || 0,
    ratingCount: Number(d.rating_count) || 0,
    created_by: d.created_by || null
  }
  const relC = database.collection(env.cloud.collections.dishTagRelation)
  const rr = await relC.where({ dish_id: it._id }).get()
  const rels = rr?.data || []
  const tagIds = Array.from(new Set(rels.map(x => x.tag_id).filter(Boolean)))
  let tags = []
  if (tagIds.length) {
    const _ = database.command
    const tagC = database.collection(env.cloud.collections.dishTag)
    const tr = await tagC.where({ _id: _.in(tagIds) }).get()
    tags = (tr?.data || []).map(t => t.name).filter(Boolean)
  }
  it.tags = tags
  return it
}

export async function createOrderForDish(dishId, qty = 1, note = '') {
  const database = db()
  const dishC = database.collection(env.cloud.collections.dish)
  const dr = await dishC.where({ _id: dishId }).get()
  const d = (dr?.data || [])[0]
  if (!d) throw new Error('菜品不存在')
  const uid = currentUserId()
  const cookId = d.created_by || 1
  const total = (Number(d.price) || 0) * (Number(qty) || 1)
  const ts = Math.floor(Date.now() / 1000)
  const nn = new Date()
  const pad = n => String(n).padStart(2, '0')
  const orderNo = `ORDER${nn.getFullYear()}${pad(nn.getMonth() + 1)}${pad(nn.getDate())}${pad(nn.getHours())}${pad(nn.getMinutes())}${pad(nn.getSeconds())}${Math.random().toString(16).slice(2, 6).toUpperCase()}`
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  const or = await orderC.add({ data: { order_no: orderNo, user_id: uid, cook_id: cookId, total_amount: total, status: 1, note: note || '', created_at: ts, updated_at: ts } })
  const oid = or?._id || ''
  if (!oid) return ''
  await itemC.add({ data: { order_id: oid, dish_id: d._id || d.id, dish_name: d.name || '', price: Number(d.price) || 0, quantity: Number(qty) || 1, created_at: ts, updated_at: ts } })
  return oid
}

export async function createOrderFromFavorites(favoriteItems) {
  if (!Array.isArray(favoriteItems) || favoriteItems.length === 0) {
    throw new Error('心选列表为空')
  }
  const database = db()
  const dishC = database.collection(env.cloud.collections.dish)
  const _ = database.command
  
  // 获取所有菜品ID
  const dishIds = favoriteItems.map(item => item._id).filter(Boolean)
  const dr = await dishC.where({ _id: _.in(dishIds) }).get()
  const dishes = dr?.data || []
  const dishMap = new Map(dishes.map(d => [d._id || d.id, d]))
  
  // 计算总价和获取厨师ID（默认取第一个菜品的创建者）
  let totalAmount = 0
  const firstDish = dishMap.get(favoriteItems[0]._id)
  const cookId = firstDish?.created_by || 1
  const uid = currentUserId()
  const ts = Math.floor(Date.now() / 1000)
  
  // 生成订单号
  const nn = new Date()
  const pad = n => String(n).padStart(2, '0')
  const orderNo = `ORDER${nn.getFullYear()}${pad(nn.getMonth() + 1)}${pad(nn.getDate())}${pad(nn.getHours())}${pad(nn.getMinutes())}${pad(nn.getSeconds())}${Math.random().toString(16).slice(2, 6).toUpperCase()}`
  
  // 计算总价并准备订单项
  const orderItems = []
  favoriteItems.forEach(item => {
    const dish = dishMap.get(item._id)
    if (dish) {
      const price = Number(dish.price) || 0
      const qty = Number(item.qty) || 1
      totalAmount += price * qty
      orderItems.push({
        dish_id: dish._id || dish.id,
        dish_name: dish.name || '',
        price: price,
        quantity: qty,
        note: item.note || ''
      })
    }
  })
  
  if (orderItems.length === 0) {
    throw new Error('没有有效的菜品')
  }
  
  // 创建订单
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  
  // 合并所有备注
  const allNotes = favoriteItems.map(item => item.note).filter(Boolean).join('；')
  
  const or = await orderC.add({ 
    data: { 
      order_no: orderNo, 
      user_id: uid, 
      cook_id: cookId, 
      total_amount: totalAmount, 
      status: 1, 
      note: allNotes || '', 
      created_at: ts, 
      updated_at: ts 
    } 
  })
  
  const oid = or?._id || ''
  if (!oid) throw new Error('创建订单失败')
  
  // 批量添加订单项
  const itemTasks = orderItems.map(item => 
    itemC.add({ 
      data: { 
        order_id: oid, 
        dish_id: item.dish_id, 
        dish_name: item.dish_name, 
        price: item.price, 
        quantity: item.quantity, 
        created_at: ts, 
        updated_at: ts 
      } 
    })
  )
  
  await Promise.all(itemTasks)
  
  return oid
}

export async function getCookOrders(limit = 20) {
  const database = db()
  const cookId = currentUserId()
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  const feedC = database.collection(env.cloud.collections.feedingRecord)
  try {
    const or = await orderC.where({ cook_id: cookId }).get()
    const orders = (or?.data || []).sort((a, b) => (b.created_at || 0) - (a.created_at || 0)).slice(0, limit)
    const ids = orders.map(o => o._id || o.id).filter(Boolean)
    if (!ids.length) return []
    const _ = database.command
    const ir = await itemC.where({ order_id: _.in(ids) }).get()
    const items = ir?.data || []
    const fr = await feedC.where({ order_id: _.in(ids) }).get()
    const feeds = fr?.data || []
    const itemMap = new Map()
    items.forEach(it => {
      const arr = itemMap.get(it.order_id) || []
      arr.push({ name: it.dish_name || '', qty: Number(it.quantity) || 1 })
      itemMap.set(it.order_id, arr)
    })
    const feedMap = new Map()
    feeds.forEach(f => feedMap.set(f.order_id, f))
    return orders.map(o => ({
      _id: o._id || o.id,
      user_id: o.user_id,
      status: Number(o.status) || 1,
      created_at: Number(o.created_at) || 0,
      items: itemMap.get(o._id || o.id) || [],
      note: (feedMap.get(o._id || o.id)?.comment) || (o.note || '')
    }))
  } catch (e) { return [] }
}

export async function updateOrderStatus(orderId, status) {
  if (!orderId) throw new Error('缺少订单ID')
  const c = db().collection(env.cloud.collections.order)
  return c.doc(orderId).update({ data: { status: Number(status) || 1, updated_at: Math.floor(Date.now()/1000) } })
}

export async function addOrderInteraction(orderId, operatorRole, actionType, content = '', score = null) {
  if (!orderId) throw new Error('缺少订单ID')
  const database = db()
  const ic = database.collection(env.cloud.collections.orderInteraction)
  const ts = Math.floor(Date.now()/1000)
  const data = { order_id: orderId, operator_id: currentUserId(), operator_role: Number(operatorRole) || 2, action_type: Number(actionType) || 1, content: content || '', score: (score == null ? null : Number(score)), created_at: ts, updated_at: ts }
  const r = await ic.add({ data })
  return r?._id || ''
}

export async function getOrderDetail(orderId) {
  if (!orderId) return null
  const database = db()
  const orderC = database.collection(env.cloud.collections.order)
  const itemC = database.collection(env.cloud.collections.orderItem)
  const feedC = database.collection(env.cloud.collections.feedingRecord)
  try {
    const or = await orderC.where({ _id: orderId }).get()
    const o = (or?.data || [])[0]
    if (!o) return null
    const ir = await itemC.where({ order_id: o._id || o.id }).get()
    const items = ir?.data || []
    const fr = await feedC.where({ order_id: o._id || o.id }).get()
    const feed = (fr?.data || [])[0] || null
    const total = items.reduce((s, i) => s + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0)
    return {
      _id: o._id || o.id,
      order_no: o.order_no || '',
      user_id: o.user_id,
      cook_id: o.cook_id,
      status: Number(o.status) || 1,
      note: o.note || '',
      created_at: Number(o.created_at) || 0,
      items: items.map(i => ({ name: i.dish_name || '', price: Number(i.price) || 0, qty: Number(i.quantity) || 1 })),
      total_amount: total,
      feed_comment: feed ? (feed.comment || '') : ''
    }
  } catch (e) { return null }
}

export async function getOrderInteractions(orderId) {
  if (!orderId) return []
  const database = db()
  const ic = database.collection(env.cloud.collections.orderInteraction)
  try {
    const r = await ic.where({ order_id: orderId }).get()
    return (r?.data || []).sort((a, b) => (a.created_at || 0) - (b.created_at || 0)).map(x => ({
      role: Number(x.operator_role) || 1,
      action: Number(x.action_type) || 0,
      content: x.content || '',
      score: x.score == null ? null : Number(x.score),
      created_at: Number(x.created_at) || 0
    }))
  } catch (e) { return [] }
}

export async function getUserProfile(userId) {
  const database = db()
  const uid = userId || currentUserId()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const r = await userC.where({ _id: uid }).get()
    const u = (r?.data || [])[0] || null
    if (!u) return null
    return {
      _id: u._id || u.id,
      nickname: u.nickname || '',
      role: Number(u.role) || 1,
      avatar: u.avatar || '',
      partner_id: u.partner_id || null,
      short_id: u.short_id || ''
    }
  } catch (e) { return null }
}

export async function updateUserPrefs(userId, prefs) {
  const database = db()
  const uid = userId || currentUserId()
  const userC = database.collection(env.cloud.collections.user)
  const data = {
    noticeOrder: !!prefs.noticeOrder,
    noticeDishReady: !!prefs.noticeDishReady,
    noticeWhisper: !!prefs.noticeWhisper,
    sweetness: Number(prefs.sweetness) || 0,
    heartBounce: !!prefs.heartBounce,
    isDark: !!prefs.isDark,
    accent: String(prefs.accent || '')
  }
  try {
    const r = await userC.where({ _id: uid }).get()
    const u = (r?.data || [])[0]
    const ts = Math.floor(Date.now()/1000)
    if (u && (u._id || u.id)) {
      const nid = u._id || u.id
      await userC.doc(nid).update({ data: { ...data, updated_at: ts } })
      return true
    } else {
      await userC.add({ data: { _id: uid, nickname: '用户', role: 1, created_at: ts, updated_at: ts, ...data } })
      return true
    }
  } catch (e) { return false }
}

export async function getCollections() {
  const database = db()
  const uid = currentUserId()
  const colC = database.collection(env.cloud.collections.dishCollection)
  try {
    const r = await colC.where({ user_id: uid }).get()
    const rows = r?.data || []
    if (!rows.length) return []
    const _ = database.command
    const dishC = database.collection(env.cloud.collections.dish)
    const dr = await dishC.where({ _id: _.in(rows.map(x => x.dish_id)) }).get()
    const dishes = dr?.data || []
    const map = new Map(dishes.map(d => [d._id || d.id, d]))
    return rows.map(x => {
      const d = map.get(x.dish_id) || {}
      return { _id: d._id || d.id, name: d.name || '', imageUrl: d.cover_image || '' }
    })
  } catch (e) { return [] }
}

export async function toggleCollection(dishId, on = true) {
  const database = db()
  const uid = currentUserId()
  const colC = database.collection(env.cloud.collections.dishCollection)
  try {
    const r = await colC.where({ user_id: uid, dish_id: dishId }).get()
    const list = r?.data || []
    if (on) {
      if (list.length) return true
      await colC.add({ data: { user_id: uid, dish_id: dishId, created_at: Math.floor(Date.now()/1000), updated_at: Math.floor(Date.now()/1000) } })
      return true
    } else {
      if (!list.length) return true
      await colC.doc(list[0]._id || list[0].id).remove()
      return true
    }
  } catch (e) { return false }
}

export async function getDishRatings(dishId, limit = 20) {
  if (!dishId) return []
  const database = db()
  const rc = database.collection(env.cloud.collections.dishRating)
  try {
    const r = await rc.where({ dish_id: dishId }).get()
    const list = (r?.data || []).sort((a, b) => (b.created_at || 0) - (a.created_at || 0)).slice(0, limit)
    return list.map(x => ({ user_id: x.user_id, score: Number(x.score) || 0, comment: x.comment || '', created_at: Number(x.created_at) || 0 }))
  } catch (e) { return [] }
}

export async function submitDishRating(orderId, dishId, score, comment = '') {
  const database = db()
  const uid = currentUserId()
  const rc = database.collection(env.cloud.collections.dishRating)
  const ts = Math.floor(Date.now()/1000)
  try {
    const r = await rc.where({ order_id: orderId, user_id: uid, dish_id: dishId }).get()
    if (r?.data?.length) {
      const nid = r.data[0]._id || r.data[0].id
      await rc.doc(nid).update({ data: { score: Number(score) || 0, comment: comment || '', updated_at: ts } })
    } else {
      await rc.add({ data: { order_id: orderId, user_id: uid, dish_id: dishId, score: Number(score) || 0, comment: comment || '', created_at: ts, updated_at: ts } })
    }
    const dishC = database.collection(env.cloud.collections.dish)
    const dr = await dishC.where({ _id: dishId }).get()
    const d = (dr?.data || [])[0]
    if (d) {
      const cnt = Number(d.rating_count) || 0
      const avg = Number(d.avg_score) || 0
      const ncnt = r?.data?.length ? cnt : (cnt + 1)
      const navg = ncnt ? Math.round(((avg * cnt + (Number(score) || 0)) / ncnt) * 10) / 10 : 0
      await dishC.doc(d._id || d.id).update({ data: { rating_count: ncnt, avg_score: navg, updated_at: ts } })
    }
    await addOrderInteraction(orderId, 1, 6, comment || '', Number(score) || 0)
    return true
  } catch (e) { return false }
}

export async function getMessages(partnerId, limit = 50) {
  const database = db()
  const uid = currentUserId()
  const mc = database.collection(env.cloud.collections.message)
  try {
    const r = await mc.where({ sender_id: uid, receiver_id: partnerId }).get()
    const r2 = await mc.where({ sender_id: partnerId, receiver_id: uid }).get()
    const list = [...(r?.data || []), ...(r2?.data || [])].sort((a, b) => (a.created_at || 0) - (b.created_at || 0))
    return list.slice(-limit).map(x => ({ from: x.sender_id, to: x.receiver_id, content: x.content || '', is_read: !!x.is_read, created_at: Number(x.created_at) || 0 }))
  } catch (e) { return [] }
}

export async function sendMessage(partnerId, content) {
  const database = db()
  const uid = currentUserId()
  const mc = database.collection(env.cloud.collections.message)
  const ts = Math.floor(Date.now()/1000)
  try {
    const r = await mc.add({ data: { sender_id: uid, receiver_id: partnerId, content: String(content || ''), is_read: 0, created_at: ts, updated_at: ts } })
    return r?._id || ''
  } catch (e) { return '' }
}

export async function markMessagesRead(partnerId) {
  const database = db()
  const uid = currentUserId()
  const mc = database.collection(env.cloud.collections.message)
  try {
    const r = await mc.where({ sender_id: partnerId, receiver_id: uid, is_read: 0 }).get()
    const list = r?.data || []
    const ts = Math.floor(Date.now()/1000)
    const tasks = list.map(m => mc.doc(m._id || m.id).update({ data: { is_read: 1, updated_at: ts } }))
    if (tasks.length) await Promise.all(tasks)
    return tasks.length
  } catch (e) { return 0 }
}

function roleNumber(role) { const r = String(role || '').toLowerCase(); return r === 'female' ? 1 : 2 }

/**
 * 生成或验证用户的短ID
 * 尝试生成新的短ID并检查重复，最多尝试10次
 */
async function generateUniqueUserShortId(database) {
  const userC = database.collection(env.cloud.collections.user)
  let shortId = ''
  let attempts = 0
  const maxAttempts = 10

  while (attempts < maxAttempts) {
    shortId = generateUniqueShortId()
    // 检查是否已存在该短ID
    const existing = await userC.where({ short_id: shortId }).get()
    if (!existing.data || existing.data.length === 0) {
      return shortId
    }
    attempts++
  }

  // 如果失败，使用纯随机ID（更长）
  return generateUniqueShortId()
}

export async function ensureCurrentUser(role) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const uid = uni.getStorageSync('user_id')
    if (uid) {
      // 更新已有用户的角色
      const r = await userC.where({ _id: uid }).get()
      if (r?.data?.length) {
        await userC.doc(uid).update({ data: { role: roleNumber(role), updated_at: Math.floor(Date.now()/1000) } })
      } else {
        // 用户ID存在但在数据库中找不到，重新创建
        const ts = Math.floor(Date.now()/1000)
        const shortId = await generateUniqueUserShortId(database)
        await userC.add({ data: { _id: uid, short_id: shortId, nickname: '用户', role: roleNumber(role), created_at: ts, updated_at: ts } })
      }
      return uid
    } else {
      // 创建新用户
      const ts = Math.floor(Date.now()/1000)
      const shortId = await generateUniqueUserShortId(database)
      const r = await userC.add({ data: { short_id: shortId, nickname: '用户', role: roleNumber(role), created_at: ts, updated_at: ts } })
      const nid = r?._id || ''
      if (nid) {
        uni.setStorageSync('user_id', nid)
        // 同时存储短ID方便显示
        uni.setStorageSync('user_short_id', shortId)
        return nid
      }
      return ''
    }
  } catch (e) { return '' }
}

export async function setUserRole(role) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  const uid = currentUserId()
  try { await userC.doc(uid).update({ data: { role: roleNumber(role), updated_at: Math.floor(Date.now()/1000) } }); return true } catch(e) { return false }
}

export async function checkUserExists(userId) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const r = await userC.where({ _id: userId }).get()
    const list = r?.data || []
    return list.length > 0
  } catch (e) { return false }
}

// 通过短ID查询用户
export async function getUserByShortId(shortId) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  try {
    // 转换为大写进行查询（因为数据库中存储的是大写）
    const r = await userC.where({ short_id: shortId.toUpperCase() }).get()
    const u = (r?.data || [])[0]
    if (!u) return null
    return {
      _id: u._id || u.id,
      nickname: u.nickname || '',
      role: Number(u.role) || 1,
      short_id: u.short_id || ''
    }
  } catch (e) { return null }
}

// 获取当前用户的角色
export async function getUserRole(userId) {
  const database = db()
  const uid = userId || currentUserId()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const r = await userC.where({ _id: uid }).get()
    const u = (r?.data || [])[0]
    if (!u) return null
    return Number(u.role) || 1
  } catch (e) { return null }
}

// 检查用户是否已有情侣绑定
export async function hasPartner(userId) {
  const database = db()
  const uid = userId || currentUserId()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const r = await userC.where({ _id: uid }).get()
    const u = (r?.data || [])[0]
    if (!u) return false
    return !!u.partner_id
  } catch (e) { return false }
}

// 创建绑定申请（邀请）
export async function createBindingRequest(targetUserInput) {
  const database = db()
  const uid = currentUserId()
  const ts = Math.floor(Date.now()/1000)
  
  console.log('=== 开始创建绑定申请 ===')
  console.log('当前用户ID:', uid)
  console.log('目标用户输入:', targetUserInput)
  
  // 接受长或短ID，自动判断查询
  const userC = database.collection(env.cloud.collections.user)
  let targetUser = null
  let targetUserId = targetUserInput
  
  // 先尝试用短ID查询（如果是6-8位的ID）
  const isShortId = isValidShortId(targetUserInput)
  console.log('是否为有效短ID格式:', isShortId)
  
  if (isShortId) {
    // 转换为大写进行查询（因为数据库中存储的是大写）
    const searchId = targetUserInput.toUpperCase()
    console.log('用短ID查询，搜索:', searchId)
    const r = await userC.where({ short_id: searchId }).get()
    console.log('短ID查询结果:', r?.data?.length || 0, '条记录')
    if (r?.data?.length) {
      targetUser = r.data[0]
      targetUserId = targetUser._id || targetUser.id
      console.log('找到用户，ID:', targetUserId, '昵称:', targetUser.nickname)
    }
  }
  
  // 如果短ID查询失败，尝试用长ID查询
  if (!targetUser) {
    console.log('用长ID查询，搜索:', targetUserInput)
    const r = await userC.where({ _id: targetUserInput }).get()
    console.log('长ID查询结果:', r?.data?.length || 0, '条记录')
    if (r?.data?.length) {
      targetUser = r.data[0]
      targetUserId = targetUser._id || targetUser.id
      console.log('找到用户，ID:', targetUserId, '昵称:', targetUser.nickname)
    }
  }
  
  if (!targetUser) {
    console.log('❌ 用户不存在')
    return { success: false, message: '用户不存在' }
  }
  
  // 不能绑定自己
  if (targetUserId === uid) {
    console.log('❌ 不能绑定自己')
    return { success: false, message: '不能绑定自己' }
  }
  
  // 验证不能同性绑定
  const currentRole = await getUserRole(uid)
  const targetRole = Number(targetUser.role) || 1
  console.log('当前用户角色:', currentRole, '目标用户角色:', targetRole)
  if (currentRole === targetRole) {
    console.log('❌ 不能与同性绑定')
    return { success: false, message: '不能与同性绑定' }
  }
  
  // 验证当前用户是否已有情侣
  const currentUser = await getUserProfile(uid)
  if (currentUser && currentUser.partner_id) {
    console.log('❌ 你已有绑定的情侣')
    return { success: false, message: '你已有绑定的情侣' }
  }
  
  // 验证目标用户是否已有情侣
  if (targetUser.partner_id) {
    console.log('❌ 对方已有绑定的情侣')
    return { success: false, message: '对方已有绑定的情侣' }
  }
  
  // 检查是否已有待处理的申请
  const reqC = database.collection(env.cloud.collections.bindingRequest)
  const existingR = await reqC.where({ from_id: uid, to_id: targetUserId, status: 0 }).get()
  if ((existingR?.data || []).length > 0) {
    console.log('❌ 已有待处理的申请')
    return { success: false, message: '已有待处理的申请' }
  }
  
  // 检查是否有其他人的待处理申请给该用户
  const otherReqR = await reqC.where({ to_id: targetUserId, status: 0 }).get()
  if ((otherReqR?.data || []).length > 0) {
    console.log('❌ 对方已有待处理的绑定申请')
    return { success: false, message: '对方已有待处理的绑定申请' }
  }
  
  // 创建申请
  try {
    console.log('✅ 创建绑定申请...')
    const r = await reqC.add({ data: { from_id: uid, to_id: targetUserId, status: 0, created_at: ts, updated_at: ts } })
    console.log('✅ 申请已发送，请求ID:', r?._id)
    return { success: true, requestId: r?._id || '', message: '申请已发送' }
  } catch (e) { 
    console.error('❌ 创建绑定申请失败:', e)
    return { success: false, message: '发送申请失败: ' + (e.message || '未知错误') } 
  }
}

// 获取待处理的绑定申请
export async function getPendingBindingRequest(userId) {
  const database = db()
  const uid = userId || currentUserId()
  const reqC = database.collection(env.cloud.collections.bindingRequest)
  try {
    const r = await reqC.where({ to_id: uid, status: 0 }).get()
    const reqs = r?.data || []
    if (!reqs.length) return null
    const req = reqs[0]
    return {
      _id: req._id || req.id,
      from_id: req.from_id,
      to_id: req.to_id,
      status: req.status,
      created_at: req.created_at
    }
  } catch (e) { return null }
}

// 接受绑定申请
export async function acceptBindingRequest(requestId) {
  const database = db()
  const uid = currentUserId()
  const ts = Math.floor(Date.now()/1000)
  const reqC = database.collection(env.cloud.collections.bindingRequest)
  const userC = database.collection(env.cloud.collections.user)
  
  try {
    // 获取申请详情
    const rr = await reqC.where({ _id: requestId, to_id: uid, status: 0 }).get()
    const req = (rr?.data || [])[0]
    if (!req) return { success: false, message: '申请不存在或已处理' }
    
    const fromId = req.from_id
    const toId = req.to_id
    
    // 更新申请状态为已接受
    await reqC.doc(requestId).update({ data: { status: 1, updated_at: ts } })
    
    // 双向绑定
    await userC.doc(fromId).update({ data: { partner_id: toId, updated_at: ts } })
    await userC.doc(toId).update({ data: { partner_id: fromId, updated_at: ts } })
    
    return { success: true, message: '绑定成功' }
  } catch (e) { return { success: false, message: '操作失败' } }
}

// 拒绝绑定申请
export async function rejectBindingRequest(requestId) {
  const database = db()
  const uid = currentUserId()
  const ts = Math.floor(Date.now()/1000)
  const reqC = database.collection(env.cloud.collections.bindingRequest)
  
  try {
    const rr = await reqC.where({ _id: requestId, to_id: uid, status: 0 }).get()
    const req = (rr?.data || [])[0]
    if (!req) return { success: false, message: '申请不存在或已处理' }
    
    // 更新申请状态为已拒绝
    await reqC.doc(requestId).update({ data: { status: 2, updated_at: ts } })
    
    return { success: true, message: '已拒绝' }
  } catch (e) { return { success: false, message: '操作失败' } }
}

// 获取用户的partner信息（包抬昵称等）
export async function getPartnerInfo(partnerId) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  try {
    const r = await userC.where({ _id: partnerId }).get()
    const u = (r?.data || [])[0]
    if (!u) return null
    return {
      _id: u._id || u.id,
      nickname: u.nickname || '',
      role: Number(u.role) || 1,
      short_id: u.short_id || ''
    }
  } catch (e) { return null }
}

export async function bindPartner(partnerId) {
  const database = db()
  const userC = database.collection(env.cloud.collections.user)
  const uid = currentUserId()
  const ts = Math.floor(Date.now()/1000)
  try {
    await userC.doc(uid).update({ data: { partner_id: partnerId, updated_at: ts } })
    try { await userC.doc(partnerId).update({ data: { partner_id: uid, updated_at: ts } }) } catch(e) {}
    return true
  } catch (e) { return false }
}
