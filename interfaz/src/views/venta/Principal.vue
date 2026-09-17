<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  BadgeDollarSign, Box, CheckCircle2, ImageOff, Minus, PackageOpen,
  Plus, ReceiptText, RefreshCw, Save, Search, ShoppingCart, Store, Trash2, X,
} from '@lucide/vue'
import Ruta from '../../components/ui/Breadcrumb.vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const propiedades = defineProps({ venta: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'actualizar'])
const catalogos = ref({ series: [], estados: [], sucursales: [], formas_pago: [], monedas: [], categorias: [], clientes: [] })
const productos = ref([])
const carrito = ref([])
const cargando = ref(true)
const cargandoProductos = ref(false)
const procesando = ref(false)
const error = ref('')
const imagenesConError = reactive({})
const busqueda = ref('')
const categoriaId = ref('')
const ventaId = ref(null)
const ultimaVenta = ref(null)
const datosVentaDialog = ref(null)
const accionPendiente = ref('guardar')
const formulario = reactive({ sucursal_id: '', venta_serie_id: '', moneda_id: '', forma_pago_id: '', cliente_id: '', tipo_cambio: 1, referencia: '' })

const simbolo = computed(() => catalogos.value.monedas.find(item => String(item.id) === String(formulario.moneda_id))?.simbolo || 'Q')
const totalUnidades = computed(() => carrito.value.reduce((total, linea) => total + Number(linea.cantidad || 0), 0))
const subtotal = computed(() => carrito.value.reduce((total, linea) => total + Number(linea.cantidad || 0) * Number(linea.precio || 0), 0))
const productosFiltrados = computed(() => {
  const termino = busqueda.value.trim().toLocaleLowerCase('es')
  return productos.value.filter(producto =>
    (!categoriaId.value || String(producto.categoria_id) === String(categoriaId.value)) &&
    (!termino || [producto.codigo, producto.codigo_barra, producto.nombre, producto.nombre_marca]
      .some(valor => String(valor || '').toLocaleLowerCase('es').includes(termino))))
})

function seleccionarPrimeros() {
  if (!formulario.sucursal_id && catalogos.value.sucursales.length) formulario.sucursal_id = catalogos.value.sucursales[0].id
  if (!formulario.venta_serie_id && catalogos.value.series.length) formulario.venta_serie_id = catalogos.value.series[0].id
  if (!formulario.moneda_id && catalogos.value.monedas.length) formulario.moneda_id = catalogos.value.monedas[0].id
  if (!formulario.forma_pago_id && catalogos.value.formas_pago.length) formulario.forma_pago_id = catalogos.value.formas_pago[0].id
}

async function cargarCatalogos() {
  cargando.value = true
  error.value = ''
  try {
    const { data } = await api.get('index.php/venta/venta/get_datos')
    const requeridos = ['series', 'estados', 'sucursales', 'formas_pago', 'monedas', 'categorias', 'clientes']
    if (!data.cat || !requeridos.every(clave => Array.isArray(data.cat[clave]))) throw new Error('No se pudieron cargar los datos para vender.')
    catalogos.value = data.cat
    seleccionarPrimeros()
    return true
  } catch (problema) {
    error.value = problema.message || 'No se pudo preparar el punto de venta.'
    return false
  } finally {
    cargando.value = false
  }
}

async function cargarProductos() {
  if (!formulario.sucursal_id) {
    productos.value = []
    return
  }
  cargandoProductos.value = true
  try {
    const { data } = await api.get('index.php/venta/venta/productos', { params: { sucursal_id: formulario.sucursal_id } })
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el catálogo disponible.')
    productos.value = data.lista
  } catch (problema) {
    productos.value = []
    toast.error(problema.message || 'No se pudieron cargar los productos disponibles.')
  } finally {
    cargandoProductos.value = false
  }
}

function existencia(producto) {
  return producto.tipo_producto === 'S' ? Infinity : Number(producto.existencia || 0)
}

function agregar(producto) {
  const actual = carrito.value.find(linea => String(linea.producto_id) === String(producto.id))
  if (actual) {
    if (actual.cantidad >= existencia(producto)) return toast.info('No hay más existencia disponible para este producto.')
    actual.cantidad = Number(actual.cantidad) + 1
    return
  }
  if (existencia(producto) <= 0) return toast.info('Este producto no tiene existencia disponible.')
  carrito.value.push({
    producto_id: producto.id,
    codigo: producto.codigo,
    nombre: producto.nombre,
    nombre_unidad: producto.nombre_unidad,
    tipo_producto: producto.tipo_producto,
    existencia: producto.existencia,
    precio: Number(producto.precio || 0),
    foto: producto.foto,
    cantidad: 1,
    descuento: 0,
  })
}

function cambiarCantidad(linea, cambio) {
  const producto = productos.value.find(item => String(item.id) === String(linea.producto_id)) || linea
  const nueva = Number(linea.cantidad) + cambio
  if (nueva <= 0) return quitar(linea)
  if (nueva > existencia(producto)) return toast.info('Alcanzaste la existencia disponible.')
  linea.cantidad = nueva
}

function quitar(linea) {
  const indice = carrito.value.indexOf(linea)
  if (indice >= 0) carrito.value.splice(indice, 1)
}

async function limpiarVenta() {
  if (propiedades.venta) {
    await cargarVenta()
    return
  }
  carrito.value = []
  ventaId.value = null
  formulario.referencia = ''
  ultimaVenta.value = null
}

async function cargarVenta() {
  const venta = propiedades.venta
  if (!venta?.id) return
  ventaId.value = venta.id
  ultimaVenta.value = venta
  formulario.sucursal_id = venta.sucursal_id
  formulario.venta_serie_id = venta.venta_serie_id
  formulario.moneda_id = venta.moneda_id
  formulario.cliente_id = venta.cliente_id || ''
  formulario.forma_pago_id = venta.forma_pago_id
  formulario.tipo_cambio = Number(venta.tipo_cambio || 1)
  formulario.referencia = venta.referencia || ''
  await cargarProductos()
  try {
    const { data } = await api.get(`index.php/venta/venta/detalle/${encodeURIComponent(venta.id)}`)
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el detalle de la venta.')
    carrito.value = data.lista.map(linea => {
      const producto = productos.value.find(item => String(item.id) === String(linea.producto_id))
      return {
        producto_id: linea.producto_id,
        codigo: linea.codigo,
        nombre: linea.nombre_producto,
        nombre_unidad: linea.nombre_unidad,
        tipo_producto: linea.tipo_producto,
        existencia: producto?.existencia ?? linea.cantidad,
        precio: Number(linea.precio || 0),
        foto: linea.foto,
        cantidad: Number(linea.cantidad || 0),
        descuento: Number(linea.descuento || 0),
      }
    })
  } catch (problema) {
    toast.error(problema.message || 'No se pudo cargar el detalle de la venta.')
  }
}

function imagenProducto(producto) {
  return producto.foto ? `https://lh3.googleusercontent.com/d/${encodeURIComponent(producto.foto)}` : ''
}

function dinero(valor) {
  return `${simbolo.value} ${Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function validarBase() {
  if (!formulario.sucursal_id) {
    toast.error('Selecciona la sucursal de la venta.')
    return false
  }
  if (!carrito.value.length) {
    toast.error('Agrega al menos un producto a la venta.')
    return false
  }
  return true
}

function validarDatosVenta() {
  if (!formulario.venta_serie_id || !formulario.moneda_id || !formulario.forma_pago_id) {
    toast.error('Completa los datos obligatorios de la venta.')
    return false
  }
  if (!Number.isFinite(Number(formulario.tipo_cambio)) || Number(formulario.tipo_cambio) <= 0) {
    toast.error('Revisa el tipo de cambio.')
    return false
  }
  return true
}

function solicitar(accion) {
  if (procesando.value || !validarBase()) return
  accionPendiente.value = accion
  datosVentaDialog.value?.showModal()
}

async function enviar(accion) {
  if (procesando.value || !validarBase() || !validarDatosVenta()) return
  procesando.value = true
  try {
    const payload = {
      ...formulario,
      tipo_cambio: Number(formulario.tipo_cambio || 1),
      detalle: carrito.value.map(linea => ({ producto_id: linea.producto_id, cantidad: Number(linea.cantidad), descuento: Number(linea.descuento || 0) })),
    }
    const sufijo = ventaId.value ? `/${encodeURIComponent(ventaId.value)}` : ''
    const { data } = await api.post(`index.php/venta/venta/${accion}${sufijo}`, payload)
    if (Number(data.exito) !== 1 || !data.linea?.id) throw new Error(data.mensaje || 'No fue posible guardar la venta.')
    toast.success(data.mensaje)
    datosVentaDialog.value?.close()
    if (accion === 'guardar') {
      ventaId.value = data.linea.id
      ultimaVenta.value = data.linea
    } else {
      ultimaVenta.value = data.linea
      carrito.value = []
      ventaId.value = null
      formulario.referencia = ''
      await cargarProductos()
    }
    emitir('actualizar', data.linea)
  } catch (problema) {
    toast.error(problema.message || 'No fue posible procesar la venta.')
  } finally {
    procesando.value = false
  }
}

watch(() => formulario.sucursal_id, async (actual, anterior) => {
  if (anterior && String(actual) !== String(anterior) && carrito.value.length) {
    carrito.value = []
    ventaId.value = null
    toast.info('El carrito se limpió porque cambiaste de sucursal.')
  }
  await cargarProductos()
})

onMounted(async () => {
  const preparado = await cargarCatalogos()
  if (preparado && propiedades.venta) await cargarVenta()
})
</script>

<template>
  <div class="sale-page">
    <header class="sale-page-header">
      <div>
        <Ruta :items="[{ label: 'Ventas' }, { label: 'Nueva venta' }]" />
        <div class="mt-3 flex items-center gap-3"><span class="sale-title-icon"><BadgeDollarSign :size="22" /></span><h1>Nueva venta</h1></div>
      </div>
      <div class="sale-page-actions"><label class="sale-branch-compact"><Store :size="17" /><span>Sucursal</span><VSelect v-model="formulario.sucursal_id" class="logy-select sale-branch-select" :options="catalogos.sucursales" label="nombre" :reduce="item => item.id" :clearable="false" :disabled="procesando || Boolean(ventaId)" placeholder="Seleccionar"><template #no-options>No hay sucursales.</template></VSelect></label><button type="button" class="sale-secondary-button" :disabled="procesando" @click="emitir('cerrar')"><ReceiptText :size="16" />Ver ventas</button><button type="button" class="sale-secondary-button" :disabled="procesando" @click="limpiarVenta"><RefreshCw :size="16" />{{ propiedades.venta ? 'Restablecer' : 'Limpiar' }}</button></div>
    </header>

    <div v-if="ultimaVenta?.correlativo" class="sale-success" role="status">
      <CheckCircle2 :size="19" /><span>Venta generada correctamente</span><strong>{{ ultimaVenta.correlativo }}</strong>
    </div>

    <div v-if="cargando" class="sale-state"><RefreshCw :size="22" class="animate-spin" /><div><strong>Preparando venta</strong><span>Cargando series, sucursales y formas de pago…</span></div></div>
    <div v-else-if="error" class="sale-state sale-state--error"><strong>No pudimos preparar el módulo</strong><span>{{ error }}</span><button type="button" @click="cargarCatalogos">Reintentar</button></div>

    <template v-else>
      <div class="sale-workspace">
        <main class="sale-catalog">
          <div class="sale-catalog-toolbar">
            <div class="sale-search"><Search :size="17" /><input v-model="busqueda" type="search" placeholder="Buscar por código, barra o producto…" /></div>
            <span>{{ productosFiltrados.length }} disponibles</span>
          </div>
          <div class="sale-categories" aria-label="Categorías">
            <button type="button" :class="{ active: categoriaId === '' }" @click="categoriaId = ''"><Box :size="15" />Todos</button>
            <button v-for="categoria in catalogos.categorias" :key="categoria.id" type="button" :class="{ active: String(categoriaId) === String(categoria.id) }" @click="categoriaId = categoria.id">{{ categoria.nombre }}</button>
          </div>

          <div v-if="cargandoProductos" class="sale-products-state"><RefreshCw :size="20" class="animate-spin" />Consultando existencias…</div>
          <div v-else-if="!formulario.sucursal_id" class="sale-products-state"><Store :size="25" />Selecciona una sucursal para consultar sus productos.</div>
          <div v-else-if="!productosFiltrados.length" class="sale-products-state"><PackageOpen :size="27" />No hay productos disponibles con estos filtros.</div>
          <div v-else class="sale-products-grid">
            <button v-for="producto in productosFiltrados" :key="producto.id" type="button" class="sale-product-card" @click="agregar(producto)">
              <div class="sale-product-image">
                <img v-if="imagenProducto(producto) && !imagenesConError[producto.id]" :src="imagenProducto(producto)" :alt="producto.nombre" @error="imagenesConError[producto.id] = true" />
                <span v-else><ImageOff :size="26" /><small>Sin imagen</small></span>
                <i :class="producto.tipo_producto === 'S' ? 'service' : ''">{{ producto.tipo_producto === 'S' ? 'Servicio' : `${Number(producto.existencia).toLocaleString('es-GT')} ${producto.codigo_unidad}` }}</i>
              </div>
              <div class="sale-product-copy"><small>{{ producto.nombre_categoria }}</small><strong>{{ producto.nombre }}</strong><span>{{ producto.codigo }}</span><b>{{ dinero(producto.precio) }}</b></div>
            </button>
          </div>
        </main>

        <aside class="sale-cart">
          <header><div><span class="sale-cart-icon"><ShoppingCart :size="19" /></span><div><h2>Detalle de venta</h2><p>{{ carrito.length }} {{ carrito.length === 1 ? 'producto' : 'productos' }}</p></div></div><span class="sale-cart-count">{{ totalUnidades.toLocaleString('es-GT') }}</span></header>
          <div v-if="!carrito.length" class="sale-cart-empty"><ShoppingCart :size="30" /><strong>El carrito está vacío</strong><span>Selecciona productos del catálogo para comenzar.</span></div>
          <div v-else class="sale-cart-lines">
            <article v-for="linea in carrito" :key="linea.producto_id" class="sale-cart-line">
              <div class="sale-line-top"><div><strong>{{ linea.nombre }}</strong><span>{{ linea.codigo }} · {{ dinero(linea.precio) }}</span></div><button type="button" title="Quitar" aria-label="Quitar producto" @click="quitar(linea)"><Trash2 :size="15" /></button></div>
              <div class="sale-line-bottom"><div class="sale-quantity"><button type="button" aria-label="Reducir cantidad" @click="cambiarCantidad(linea, -1)"><Minus :size="14" /></button><input v-model.number="linea.cantidad" type="number" min="0.01" :max="linea.tipo_producto === 'S' ? undefined : linea.existencia" step="0.01" /><button type="button" aria-label="Aumentar cantidad" @click="cambiarCantidad(linea, 1)"><Plus :size="14" /></button></div><strong>{{ dinero(Number(linea.cantidad) * Number(linea.precio)) }}</strong></div>
            </article>
          </div>
          <footer>
            <dl><div><dt>Unidades</dt><dd>{{ totalUnidades.toLocaleString('es-GT') }}</dd></div><div><dt>Descuento</dt><dd>{{ dinero(0) }}</dd></div><div class="sale-total"><dt>Total</dt><dd>{{ dinero(subtotal) }}</dd></div></dl>
            <div class="sale-cart-actions"><button type="button" class="sale-draft-button" :disabled="procesando || !carrito.length" @click="solicitar('guardar')"><Save :size="16" />{{ ventaId ? 'Actualizar' : 'Guardar' }}</button><button type="button" class="btn-primary sale-invoice-button" :disabled="procesando || !carrito.length" @click="solicitar('facturar')"><RefreshCw v-if="procesando" :size="16" class="animate-spin" /><ReceiptText v-else :size="16" />{{ procesando ? 'Procesando…' : 'Facturar' }}</button></div>
          </footer>
        </aside>
      </div>
    </template>

    <dialog ref="datosVentaDialog" class="sale-dialog" aria-labelledby="titulo-datos-venta" @cancel.prevent="!procesando && datosVentaDialog.close()">
      <header>
        <div class="sale-dialog-title"><span><ReceiptText :size="20" /></span><div><h2 id="titulo-datos-venta">{{ accionPendiente === 'facturar' ? 'Facturar venta' : ventaId ? 'Actualizar borrador' : 'Guardar borrador' }}</h2><p>Completa los datos comerciales del comprobante</p></div></div>
        <button type="button" class="sale-dialog-close" :disabled="procesando" aria-label="Cerrar" @click="datosVentaDialog.close()"><X :size="20" /></button>
      </header>
      <div class="sale-dialog-body">
        <div class="sale-dialog-summary"><span>{{ carrito.length }} {{ carrito.length === 1 ? 'producto' : 'productos' }}</span><strong>{{ dinero(subtotal) }}</strong></div>
        <fieldset :disabled="procesando" class="sale-dialog-fields">
          <legend class="sr-only">Datos comerciales de la venta</legend>
          <label><span>Serie <b>*</b></span><VSelect v-model="formulario.venta_serie_id" class="logy-select" :options="catalogos.series" :get-option-label="item => `${item.nombre} · ${item.codigo}`" :reduce="item => item.id" :clearable="false" :disabled="procesando" placeholder="Seleccionar serie"><template #no-options>No hay series.</template></VSelect></label>
          <label><span>Cliente</span><VSelect v-model="formulario.cliente_id" class="logy-select" :options="catalogos.clientes" :get-option-label="item => `${item.nombre}${item.identificacion ? ` · ${item.identificacion}` : ''}`" :reduce="item => item.id" :disabled="procesando" placeholder="Consumidor final"><template #no-options>No hay clientes coincidentes.</template></VSelect></label>
          <label><span>Forma de pago <b>*</b></span><VSelect v-model="formulario.forma_pago_id" class="logy-select" :options="catalogos.formas_pago" label="nombre" :reduce="item => item.id" :clearable="false" :disabled="procesando" placeholder="Seleccionar forma de pago"><template #no-options>No hay formas de pago.</template></VSelect></label>
          <label><span>Moneda <b>*</b></span><VSelect v-model="formulario.moneda_id" class="logy-select" :options="catalogos.monedas" :get-option-label="item => `${item.codigo} · ${item.nombre}`" :reduce="item => item.id" :clearable="false" :disabled="procesando" placeholder="Seleccionar moneda"><template #no-options>No hay monedas.</template></VSelect></label>
          <label><span>Tipo de cambio <b>*</b></span><input v-model.number="formulario.tipo_cambio" type="number" min="0.00001" step="0.00001" /></label>
          <label class="sale-dialog-reference"><span>Referencia</span><input v-model.trim="formulario.referencia" maxlength="300" placeholder="Pedido, observación o referencia" /></label>
        </fieldset>
      </div>
      <footer><button type="button" class="sale-secondary-button" :disabled="procesando" @click="datosVentaDialog.close()">Cancelar</button><button type="button" class="btn-primary sale-dialog-confirm" :disabled="procesando" @click="enviar(accionPendiente)"><RefreshCw v-if="procesando" :size="17" class="animate-spin" /><ReceiptText v-else-if="accionPendiente === 'facturar'" :size="17" /><Save v-else :size="17" />{{ procesando ? 'Procesando…' : accionPendiente === 'facturar' ? 'Confirmar y facturar' : 'Guardar borrador' }}</button></footer>
    </dialog>
  </div>
</template>

<style scoped>
.sale-page{width:100%;max-width:1760px;margin:0 auto;padding:.9rem 1.5rem 1.5rem}.sale-page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem}.sale-page-header h1{font-size:1.4rem;font-weight:750;letter-spacing:-.025em}.sale-page-header p{margin-top:.15rem;color:var(--muted);font-size:.78rem}.sale-title-icon{display:grid;width:2.75rem;height:2.75rem;place-items:center;border:1px solid var(--line);border-radius:.75rem;background:color-mix(in srgb,var(--accent) 7%,var(--surface));color:var(--accent)}.sale-secondary-button,.sale-draft-button{display:inline-flex;min-height:2.55rem;align-items:center;justify-content:center;gap:.45rem;border:1px solid var(--line);border-radius:.55rem;background:var(--surface);padding:.55rem .85rem;color:var(--ink);font-size:.78rem;font-weight:650}.sale-success{display:flex;align-items:center;gap:.55rem;margin-bottom:1rem;border:1px solid color-mix(in srgb,#059669 25%,var(--line));border-radius:.7rem;background:color-mix(in srgb,#059669 7%,var(--surface));padding:.75rem 1rem;color:#047857;font-size:.8rem}.sale-success strong{margin-left:auto}.sale-state{display:flex;min-height:18rem;align-items:center;justify-content:center;gap:.8rem;border:1px solid var(--line);border-radius:.8rem;background:var(--surface)}.sale-state div{display:grid}.sale-state span{color:var(--muted);font-size:.75rem}.sale-state--error{flex-direction:column}.sale-state--error button{border:1px solid var(--line);border-radius:.5rem;padding:.55rem .85rem}.sale-header-card{display:grid;grid-template-columns:auto minmax(0,1fr);gap:1.2rem;align-items:center;margin-bottom:1rem;border:1px solid var(--line);border-radius:.8rem;background:var(--surface);padding:1rem 1.1rem}.sale-section-heading{display:flex;align-items:center;gap:.65rem;padding-right:1.2rem;border-right:1px solid var(--line)}.sale-section-heading>span{display:grid;width:2.25rem;height:2.25rem;place-items:center;border-radius:.55rem;background:color-mix(in srgb,var(--accent) 8%,var(--surface));color:var(--accent)}.sale-section-heading h2{font-size:.85rem;font-weight:700}.sale-section-heading p{color:var(--muted);font-size:.64rem}.sale-header-fields{display:grid;grid-template-columns:repeat(6,minmax(7rem,1fr));gap:.65rem}.sale-header-fields label>span{display:block;margin-bottom:.3rem;color:var(--muted);font-size:.65rem;font-weight:650}.sale-header-fields b{color:#dc2626}.sale-header-fields input,.sale-header-fields select{width:100%;min-height:2.35rem;border:1px solid var(--line);border-radius:.45rem;background:var(--surface);padding:.4rem .55rem;color:var(--ink);font-size:.73rem;outline:none}.sale-header-fields input:focus,.sale-header-fields select:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}.sale-workspace{display:grid;grid-template-columns:minmax(0,1fr) 22rem;gap:1rem;align-items:start}.sale-catalog,.sale-cart{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:.8rem;background:var(--surface);box-shadow:0 1px 2px rgba(15,23,42,.03)}.sale-catalog-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding:.8rem 1rem}.sale-catalog-toolbar>span{color:var(--muted);font-size:.68rem}.sale-search{position:relative;width:min(28rem,100%)}.sale-search>svg{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--muted)}.sale-search input{width:100%;min-height:2.4rem;border:1px solid var(--line);border-radius:.5rem;background:var(--canvas);padding:.45rem .75rem .45rem 2.15rem;color:var(--ink);font-size:.75rem;outline:none}.sale-categories{display:flex;gap:.45rem;overflow-x:auto;border-bottom:1px solid var(--line);padding:.65rem 1rem}.sale-categories button{display:inline-flex;min-height:2rem;flex:none;align-items:center;gap:.35rem;border:1px solid var(--line);border-radius:999px;padding:.3rem .7rem;color:var(--muted);font-size:.66rem;font-weight:650}.sale-categories button.active{border-color:color-mix(in srgb,var(--accent) 35%,var(--line));background:color-mix(in srgb,var(--accent) 9%,var(--surface));color:var(--accent)}.sale-products-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;padding:1rem}.sale-product-card{overflow:hidden;border:1px solid var(--line);border-radius:.7rem;background:var(--surface);text-align:left;transition:.15s}.sale-product-card:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--accent) 40%,var(--line));box-shadow:0 8px 22px rgba(15,23,42,.08)}.sale-product-image{position:relative;display:grid;aspect-ratio:1.65/1;place-items:center;overflow:hidden;background:var(--soft)}.sale-product-image img{width:100%;height:100%;object-fit:cover}.sale-product-image>span{display:grid;place-items:center;gap:.25rem;color:var(--muted)}.sale-product-image small{font-size:.6rem}.sale-product-image i{position:absolute;right:.45rem;bottom:.4rem;border-radius:999px;background:rgba(15,23,42,.78);padding:.24rem .46rem;color:white;font-size:.58rem;font-style:normal;font-weight:700;backdrop-filter:blur(4px)}.sale-product-image i.service{background:#2563eb}.sale-product-copy{display:grid;gap:.18rem;padding:.7rem}.sale-product-copy small{overflow:hidden;color:var(--muted);font-size:.59rem;text-overflow:ellipsis;white-space:nowrap}.sale-product-copy strong{min-height:2rem;overflow:hidden;font-size:.75rem;line-height:1rem}.sale-product-copy span{color:var(--muted);font-family:ui-monospace,monospace;font-size:.59rem}.sale-product-copy b{margin-top:.25rem;color:var(--accent);font-size:.9rem}.sale-products-state{display:flex;min-height:22rem;align-items:center;justify-content:center;gap:.55rem;color:var(--muted);font-size:.77rem}.sale-cart{position:sticky;top:5.6rem}.sale-cart>header{display:flex;min-height:4.1rem;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);padding:.8rem 1rem}.sale-cart>header>div{display:flex;align-items:center;gap:.65rem}.sale-cart h2{font-size:.84rem;font-weight:750}.sale-cart header p{color:var(--muted);font-size:.62rem}.sale-cart-icon{display:grid;width:2.15rem;height:2.15rem;place-items:center;border-radius:.55rem;background:color-mix(in srgb,var(--accent) 9%,var(--surface));color:var(--accent)}.sale-cart-count{display:grid;min-width:1.85rem;height:1.85rem;place-items:center;border-radius:999px;background:var(--soft);font-size:.68rem;font-weight:700}.sale-cart-empty{display:flex;min-height:18rem;flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center;color:var(--muted)}.sale-cart-empty strong{margin-top:.7rem;color:var(--ink);font-size:.8rem}.sale-cart-empty span{margin-top:.25rem;font-size:.68rem}.sale-cart-lines{max-height:calc(100dvh - 28rem);min-height:12rem;overflow:auto}.sale-cart-line{display:grid;gap:.65rem;border-bottom:1px solid var(--line);padding:.8rem 1rem}.sale-line-top,.sale-line-bottom{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.sale-line-top>div{display:grid;min-width:0}.sale-line-top strong{overflow:hidden;font-size:.72rem;text-overflow:ellipsis;white-space:nowrap}.sale-line-top span{color:var(--muted);font-size:.61rem}.sale-line-top button{display:grid;width:1.8rem;height:1.8rem;flex:none;place-items:center;border-radius:.4rem;color:#dc2626}.sale-line-top button:hover{background:rgba(220,38,38,.08)}.sale-line-bottom>strong{font-size:.75rem}.sale-quantity{display:flex;align-items:center}.sale-quantity button{display:grid;width:1.9rem;height:1.9rem;place-items:center;border:1px solid var(--line);background:var(--soft)}.sale-quantity button:first-child{border-radius:.4rem 0 0 .4rem}.sale-quantity button:last-child{border-radius:0 .4rem .4rem 0}.sale-quantity input{width:3.4rem;height:1.9rem;border-block:1px solid var(--line);background:var(--surface);text-align:center;font-size:.7rem;outline:none}.sale-cart>footer{border-top:1px solid var(--line);background:color-mix(in srgb,var(--soft) 25%,var(--surface));padding:1rem}.sale-cart dl{display:grid;gap:.55rem}.sale-cart dl>div{display:flex;justify-content:space-between;color:var(--muted);font-size:.7rem}.sale-cart dl dd{color:var(--ink);font-weight:650}.sale-cart .sale-total{margin-top:.25rem;border-top:1px solid var(--line);padding-top:.75rem;color:var(--ink);font-size:.9rem}.sale-cart .sale-total dd{color:var(--accent);font-size:1.2rem}.sale-cart-actions{display:grid;grid-template-columns:1fr 1.35fr;gap:.55rem;margin-top:1rem}.sale-invoice-button{display:inline-flex;min-height:2.65rem;align-items:center;justify-content:center;gap:.45rem;border-radius:.55rem;font-size:.76rem;font-weight:700}
.sale-page{padding:1.5rem}.sale-page-header{margin-bottom:1.25rem}.sale-page-header p{font-size:.875rem}.sale-page-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.6rem}.sale-secondary-button,.sale-draft-button{min-height:2.7rem;padding:.6rem 1rem;font-size:.875rem}.sale-success,.sale-state span{font-size:.875rem}.sale-header-card{grid-template-columns:minmax(17rem,auto) minmax(16rem,25rem) minmax(16rem,1fr);gap:1.5rem;margin-bottom:1.25rem;padding:1.25rem}.sale-section-heading{gap:.75rem;padding-right:1.5rem}.sale-section-heading>span{width:2.5rem;height:2.5rem;flex:none}.sale-section-heading h2{font-size:1rem}.sale-section-heading p{margin-top:.15rem;font-size:.8rem;line-height:1.2rem}.sale-branch-field label{display:block;margin-bottom:.45rem;color:var(--muted);font-size:.8rem;font-weight:650}.sale-branch-field b,.sale-dialog-fields b{color:#dc2626}.sale-branch-field select{width:100%;min-height:2.75rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.55rem .75rem;color:var(--ink);font-size:.875rem;outline:none}.sale-branch-field select:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}.sale-header-help{max-width:30rem;color:var(--muted);font-size:.8rem;line-height:1.3rem}.sale-workspace{grid-template-columns:minmax(0,1fr) 23rem;gap:1.25rem}.sale-catalog-toolbar>span{font-size:.8rem}.sale-search input{min-height:2.7rem;font-size:.875rem}.sale-categories button{min-height:2.25rem;padding:.4rem .8rem;font-size:.8rem}.sale-product-image small{font-size:.75rem}.sale-product-image i{font-size:.7rem}.sale-product-copy{gap:.25rem;padding:.85rem}.sale-product-copy small{font-size:.75rem}.sale-product-copy strong{min-height:2.3rem;font-size:.875rem;line-height:1.15rem}.sale-product-copy span{font-size:.75rem}.sale-product-copy b{font-size:1rem}.sale-products-state{font-size:.875rem}.sale-cart h2{font-size:1rem}.sale-cart header p{font-size:.75rem}.sale-cart-count{font-size:.8rem}.sale-cart-empty strong,.sale-line-top strong{font-size:.875rem}.sale-cart-empty span,.sale-line-top span{font-size:.75rem}.sale-line-bottom>strong{font-size:.875rem}.sale-quantity input{font-size:.8rem}.sale-cart dl>div{font-size:.8rem}.sale-invoice-button{font-size:.875rem}.sale-dialog{width:calc(100% - 2rem);max-width:44rem;margin:auto;max-height:calc(100dvh - 2rem);overflow:auto;border:1px solid var(--line);border-radius:.85rem;background:var(--surface);padding:0;color:var(--ink);box-shadow:0 24px 70px rgba(15,23,42,.3)}.sale-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.sale-dialog>header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);padding:1.15rem 1.25rem}.sale-dialog-title{display:flex;align-items:center;gap:.75rem}.sale-dialog-title>span{display:grid;width:2.6rem;height:2.6rem;place-items:center;border-radius:.65rem;background:color-mix(in srgb,var(--accent) 9%,var(--surface));color:var(--accent)}.sale-dialog-title h2{font-size:1.05rem;font-weight:700}.sale-dialog-title p{margin-top:.15rem;color:var(--muted);font-size:.8rem}.sale-dialog-close{display:grid;width:2.5rem;height:2.5rem;place-items:center;border-radius:.55rem;color:var(--muted)}.sale-dialog-close:hover{background:var(--soft)}.sale-dialog-body{padding:1.25rem}.sale-dialog-summary{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;border-radius:.65rem;background:color-mix(in srgb,var(--accent) 5%,var(--surface));padding:.85rem 1rem;font-size:.875rem}.sale-dialog-summary span{color:var(--muted)}.sale-dialog-summary strong{color:var(--accent);font-size:1.1rem}.sale-dialog-fields{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.sale-dialog-fields label>span{display:block;margin-bottom:.45rem;color:var(--muted);font-size:.8rem;font-weight:650}.sale-dialog-fields input,.sale-dialog-fields select{width:100%;min-height:2.75rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.55rem .75rem;color:var(--ink);font-size:.875rem;outline:none}.sale-dialog-fields input:focus,.sale-dialog-fields select:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}.sale-dialog-reference{grid-column:1/-1}.sale-dialog>footer{display:flex;justify-content:flex-end;gap:.65rem;border-top:1px solid var(--line);padding:1rem 1.25rem}.sale-dialog-confirm{display:inline-flex;min-height:2.7rem;align-items:center;justify-content:center;gap:.5rem;border-radius:.55rem;padding:.6rem 1rem;font-size:.875rem;font-weight:700}
.sale-branch-compact{display:flex;min-height:2.7rem;align-items:center;gap:.5rem;border:1px solid var(--line);border-radius:.55rem;background:var(--surface);padding:0 .35rem 0 .75rem;color:var(--muted)}.sale-branch-compact>span{font-size:.75rem;font-weight:650}.sale-branch-select{min-width:13rem;font-weight:600}.sale-branch-select :deep(.vs__dropdown-toggle){min-height:2.15rem;border:0;box-shadow:none}.sale-branch-compact:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}
@media(max-width:1450px){.sale-products-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.sale-header-fields{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:1050px){.sale-workspace{grid-template-columns:1fr}.sale-cart{position:static}.sale-cart-lines{max-height:24rem}.sale-products-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.sale-page{padding:1rem}.sale-page-header{align-items:flex-start;flex-direction:column}.sale-page-actions{width:100%;justify-content:flex-start}.sale-branch-compact{width:100%}.sale-branch-select{min-width:0;flex:1}.sale-page-actions>.sale-secondary-button{flex:1}.sale-products-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;padding:.7rem}.sale-product-copy{padding:.7rem}.sale-catalog-toolbar{align-items:flex-start;flex-direction:column}.sale-search{width:100%}.sale-dialog-fields{grid-template-columns:1fr}.sale-dialog-reference{grid-column:auto}.sale-dialog>footer{flex-direction:column-reverse}.sale-dialog>footer button{width:100%}}
@media(max-width:430px){.sale-products-grid{grid-template-columns:1fr 1fr}.sale-cart-actions{grid-template-columns:1fr}}
</style>
