<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ArrowLeft, ShoppingCart, Plus, Search, Trash2, X, Save, Printer, PackageCheck, Ban, Building2, CreditCard, Warehouse, ReceiptText, ScanLine, PackagePlus, Boxes, FileText, CircleDollarSign, Check, LoaderCircle, RotateCcw } from '@lucide/vue'
import { toast } from 'vue3-toastify'
import api from '../../services/api'
import Tabla from '../../components/ui/BaseTable.vue'
import FormProducto from '../mnt/producto/Form.vue'
const propiedades=defineProps({compra:{type:Object,default:null},imprimiendo:Boolean})
const emitir=defineEmits(['cerrar','actualizar','imprimir'])
const formulario=reactive({
 proveedor_id:propiedades.compra?.proveedor_id ?? null, forma_pago_id:propiedades.compra?.forma_pago_id ?? null,
 moneda_id:propiedades.compra?.moneda_id ?? null, sucursal_id:propiedades.compra?.sucursal_id ?? null,
 factura_numero:propiedades.compra?.factura_numero || '',factura_fecha:propiedades.compra?.factura_fecha?.slice(0,10) || '',
 referencias:propiedades.compra?.referencias || '', detalle:[]
})
const catalogos=ref({productos:[],proveedores:[],formas_pago:[],monedas:[],sucursales:[],categorias:[],marcas:[]})
const cargando=ref(true), errorCarga=ref(false), ocupado=ref(false), verProducto=ref(false)
const buscador=ref(null), confirmacion=ref(null), accion=ref('')
const cantidad=ref(1), busqueda=ref('')
const filtros=reactive({termino:'',categoria_id:'',marca_id:''})
const campos=[
 {clave:'proveedor_id',nombre:'Proveedor',catalogo:'proveedores',actual:'nombre_proveedor'},
 {clave:'forma_pago_id',nombre:'Forma de pago',catalogo:'formas_pago',actual:'nombre_forma_pago'},
 {clave:'moneda_id',nombre:'Moneda',catalogo:'monedas',actual:'nombre_moneda'},
 {clave:'sucursal_id',nombre:'Sucursal',catalogo:'sucursales',actual:'nombre_sucursal'}
]
const editable=computed(()=>!propiedades.compra || (Number(propiedades.compra.compra_estado_id)===1 && Number(propiedades.compra.anulado || 0)===0))
const puedeEditar=computed(()=>editable.value && !cargando.value && !errorCarga.value && !ocupado.value)
const detalleActivo=computed(()=>formulario.detalle.filter(linea=>Number(linea.anulado || 0)===0))
const simbolo=computed(()=>catalogos.value.monedas.find(moneda=>String(moneda.id)===String(formulario.moneda_id))?.simbolo || propiedades.compra?.simbolo_moneda || '')
const totalLinea=linea=>Number(linea.cantidad || 0)*Number(linea.precio_costo || 0)
const total=computed(()=>detalleActivo.value.reduce((suma,linea)=>suma+totalLinea(linea),0))
const totalUnidades=computed(()=>detalleActivo.value.reduce((suma,linea)=>suma+Number(linea.cantidad || 0),0))
const estadoId=computed(()=>Number(propiedades.compra?.compra_estado_id || 0))
const estadoNombre=computed(()=>propiedades.compra?.nombre_estado || 'Borrador')
const estadoClase=computed(()=>estadoId.value===2?'purchase-status purchase-status--success':estadoId.value===3?'purchase-status purchase-status--danger':'purchase-status purchase-status--primary')
const monto=valor=>simbolo.value+' '+Number(valor || 0).toLocaleString('es-GT',{minimumFractionDigits:2,maximumFractionDigits:2})
const productos=computed(()=>catalogos.value.productos.filter(producto=>
 Number(producto.activo)===1 && (!filtros.categoria_id || String(producto.categoria_id)===String(filtros.categoria_id)) &&
 (!filtros.marca_id || String(producto.marca_id)===String(filtros.marca_id)) &&
 [producto.codigo,producto.codigo_barra,producto.nombre,producto.nombre_categoria,producto.nombre_marca].some(valor=>String(valor || '').toLowerCase().includes(filtros.termino.trim().toLowerCase()))))
const columnasProductos=[{key:'codigo',label:'Código'},{key:'nombre',label:'Producto'},{key:'nombre_um',label:'Unidad'},{key:'nombre_categoria',label:'Categoría'},{key:'nombre_marca',label:'Marca'},{key:'costo',label:'Costo'},{key:'agregar',label:'Acciones'}]
const columnasDetalle=[{key:'codigo',label:'Código'},{key:'nombre_producto',label:'Producto'},{key:'nombre_um',label:'Unidad'},{key:'fecha_vence',label:'Vencimiento'},{key:'cantidad',label:'Cantidad'},{key:'precio_costo',label:'Costo'},{key:'total',label:'Total',class:'text-end'},{key:'quitar',label:'',class:'text-end'}]
const base=ref('')
const serializar=()=>JSON.stringify(formulario)
const cambios=computed(()=>base.value!==serializar())
async function cargar(){
 if(ocupado.value)return
 cargando.value=true;errorCarga.value=false
 try{
  const [datos,detalle]=await Promise.all([
   api.get('index.php/compra/orden/get_datos'),
   propiedades.compra?api.get('index.php/compra/detalle/buscar',{params:{compra_id:propiedades.compra.id}}):Promise.resolve({data:{lista:[]}})
  ])
  if(!datos.data.cat || !Object.keys(catalogos.value).every(clave=>Array.isArray(datos.data.cat[clave])) || !Array.isArray(detalle.data.lista))throw new Error('No se pudo cargar la orden.')
  catalogos.value=datos.data.cat
  formulario.detalle=detalle.data.lista.map(linea=>({...linea,fecha_vence:linea.fecha_vence?.slice(0,10) || null}))
  base.value=serializar()
 }catch(problema){errorCarga.value=true;toast.error(problema.message || 'No se pudo cargar la orden.')}
 finally{cargando.value=false}
}
function agregar(producto){
 if(!puedeEditar.value)return false
 if(!producto || Number(producto.activo)!==1){toast.error('Selecciona un producto activo.');return false}
 if(!Number.isFinite(Number(cantidad.value)) || Number(cantidad.value)<=0){toast.error('Ingresa una cantidad válida.');return false}
 const existente=detalleActivo.value.find(linea=>String(linea.producto_id)===String(producto.id))
 if(existente)existente.cantidad=Number(existente.cantidad)+Number(cantidad.value)
 else formulario.detalle.push({producto_id:producto.id,unidad_medida_id:producto.unidad_medida_id,producto_presentacion_id:null,codigo:producto.codigo,nombre_producto:producto.nombre,nombre_um:producto.nombre_um,control_vence:Number(producto.control_vence || 0),fecha_vence:null,cantidad:Number(cantidad.value),precio_costo:Number(producto.costo || 0),anulado:0})
 cantidad.value=1;busqueda.value=''
 return true
}
function agregarCodigo(){
 const termino=busqueda.value.trim().toLowerCase()
 const producto=catalogos.value.productos.find(item=>[item.codigo,item.codigo_barra].some(valor=>valor && String(valor).toLowerCase()===termino))
 if(producto)agregar(producto)
 else{filtros.termino=busqueda.value;buscador.value.showModal()}
}
function quitar(linea){
 if(!puedeEditar.value)return
 if(linea.id)linea.anulado=1
 else formulario.detalle.splice(formulario.detalle.indexOf(linea),1)
}
function productoCreado(producto){
 catalogos.value.productos.push(producto);agregar(producto);verProducto.value=false;toast.success('Producto creado.')
}
function validar(){
 if(campos.some(campo=>!formulario[campo.clave])){toast.error('Completa los campos obligatorios.');return false}
 if(!detalleActivo.value.length){toast.error('Agrega al menos un producto.');return false}
 if(detalleActivo.value.some(linea=>!linea.producto_id || !linea.unidad_medida_id || !Number.isFinite(Number(linea.cantidad)) || Number(linea.cantidad)<=0 || linea.precio_costo===null || linea.precio_costo==='' || !Number.isFinite(Number(linea.precio_costo)) || Number(linea.precio_costo)<0)){toast.error('Revisa las cantidades y los costos.');return false}
 return true
}
function solicitar(tipo){
 if(ocupado.value)return
 if(tipo==='salir'){
  if(!editable.value || !cambios.value){emitir('cerrar');return}
 }else{
  if(!puedeEditar.value)return
  if(tipo==='guardar' && !validar())return
  if((tipo==='recibir' || tipo==='anular') && !propiedades.compra)return
  if(tipo==='recibir' && cambios.value){toast.info('Guarda los cambios antes de recibir la orden.');return}
 }
 accion.value=tipo;confirmacion.value.showModal()
}
async function ejecutar(){
 const tipo=accion.value
 if(ocupado.value)return
 if(tipo==='salir'){confirmacion.value.close();emitir('cerrar');return}
 if(!['guardar','recibir','anular'].includes(tipo))return
 if(tipo!=='guardar' && !propiedades.compra)return
 if(tipo==='recibir' && cambios.value){toast.info('Guarda los cambios antes de recibir la orden.');return}
 if(!puedeEditar.value || (tipo==='guardar' && !validar()))return
 ocupado.value=true
 try{
  const datos=tipo==='guardar'?{
   proveedor_id:formulario.proveedor_id,forma_pago_id:formulario.forma_pago_id,moneda_id:formulario.moneda_id,sucursal_id:formulario.sucursal_id,
   factura_numero:formulario.factura_numero,factura_fecha:formulario.factura_fecha || null,referencias:formulario.referencias,
   detalle:formulario.detalle.map(linea=>({
    ...(linea.id?{id:linea.id}:{}),producto_id:linea.producto_id,unidad_medida_id:linea.unidad_medida_id,producto_presentacion_id:linea.producto_presentacion_id || null,
    fecha_vence:linea.fecha_vence || null,cantidad:Number(linea.cantidad),precio_costo:Number(linea.precio_costo),total_costo:totalLinea(linea),anulado:Number(linea.anulado || 0)
   }))
  }:undefined
  const {data}=await api.post('index.php/compra/orden/'+tipo+'/'+encodeURIComponent(propiedades.compra?.id || ''),datos)
  if(Number(data.exito)!==1 || !data.linea?.id)throw new Error(data.mensaje || 'No se pudo completar la operación.')
  toast.success(data.mensaje || 'Operación completada.');confirmacion.value.close();emitir('actualizar',data.linea)
 }catch(problema){toast.error(problema.message || 'No se pudo completar la operación.')}
 finally{ocupado.value=false}
}
onMounted(cargar)
</script>
<template>
 <div class="purchase-page">
  <header class="purchase-header">
   <div class="min-w-0">
    <button class="purchase-back" :disabled="ocupado" @click="solicitar('salir')"><ArrowLeft :size="15" />Compras<span>/</span>Órdenes</button>
    <div class="mt-3 flex flex-wrap items-center gap-3">
     <span class="purchase-title-icon"><ShoppingCart :size="22" /></span>
     <div><h1>{{compra?.numero || 'Nueva orden de compra'}}</h1></div>
    </div>
   </div>
   <div class="purchase-actions">
    <button v-if="compra" class="purchase-btn purchase-btn--secondary" :disabled="imprimiendo || ocupado" title="Ver PDF" @click="$emit('imprimir',compra)"><Printer :size="16" />PDF</button>
    <button v-if="compra && editable" class="purchase-btn purchase-btn--success" :disabled="!puedeEditar" @click="solicitar('recibir')"><PackageCheck :size="16" />Recibir</button>
    <button v-if="compra && editable" class="purchase-btn purchase-btn--danger" :disabled="!puedeEditar" @click="solicitar('anular')"><Ban :size="16" />Anular</button>
    <button v-if="editable" class="btn-primary purchase-btn" :disabled="!puedeEditar" @click="solicitar('guardar')"><LoaderCircle v-if="ocupado" :size="16" class="animate-spin" /><Save v-else :size="16" />{{ocupado?'Guardando…':'Guardar'}}</button>
   </div>
  </header>

  <div v-if="cargando" class="purchase-loading" role="status"><LoaderCircle :size="28" class="animate-spin text-blue-600" /><div><strong>Cargando orden</strong><span>Preparando catálogos y productos…</span></div></div>
  <div v-else-if="errorCarga" class="purchase-error"><RotateCcw :size="28" /><div><strong>No pudimos cargar la orden</strong><p>Comprueba la conexión e inténtalo nuevamente.</p></div><button class="btn-primary purchase-btn" @click="cargar">Reintentar</button></div>

  <div v-else class="purchase-layout">
   <div class="min-w-0 space-y-5">
    <section class="purchase-card">
     <div class="purchase-card-header"><div class="purchase-section-title"><span><Building2 :size="18" /></span><div><h2>Información de la compra</h2><p>Proveedor, condiciones comerciales y destino</p></div></div><span class="required-note"><b>*</b> Campos obligatorios</span></div>
     <fieldset :disabled="!puedeEditar" class="purchase-fields">
      <legend class="sr-only">Información de la compra</legend>
      <div v-for="(campo,index) in campos" :key="campo.clave" class="purchase-field">
       <label :for="'orden-'+campo.clave"><Building2 v-if="index===0" :size="14" /><CreditCard v-else-if="index===1" :size="14" /><CircleDollarSign v-else-if="index===2" :size="14" /><Warehouse v-else :size="14" />{{campo.nombre}} <b>*</b></label>
       <select :id="'orden-'+campo.clave" v-model="formulario[campo.clave]" required><option :value="null" disabled>Seleccionar {{campo.nombre.toLowerCase()}}</option><option v-for="opcion in catalogos[campo.catalogo]" :key="opcion.id" :value="opcion.id">{{opcion.nombre}}</option><option v-if="formulario[campo.clave] && !catalogos[campo.catalogo].some(opcion=>String(opcion.id)===String(formulario[campo.clave]))" :value="formulario[campo.clave]">{{compra?.[campo.actual] || formulario[campo.clave]}}</option></select>
      </div>
      <div class="purchase-field"><label for="oc-factura"><ReceiptText :size="14" />Número de factura</label><input id="oc-factura" v-model="formulario.factura_numero" placeholder="Ej. FAC-001245" /></div>
      <div class="purchase-field"><label for="oc-fecha"><ReceiptText :size="14" />Fecha de factura</label><input id="oc-fecha" v-model="formulario.factura_fecha" type="date" /></div>
     </fieldset>
    </section>

    <section class="purchase-card purchase-card--table">
     <div class="purchase-card-header"><div class="purchase-section-title"><span><Boxes :size="18" /></span><div><h2>Detalle de productos</h2><p>Agrega los artículos incluidos en esta orden</p></div></div><span class="purchase-count">{{detalleActivo.length}} {{detalleActivo.length===1?'producto':'productos'}}</span></div>
     <div v-if="editable" class="purchase-addbar">
      <div class="purchase-field purchase-field--quantity"><label for="oc-cantidad">Cantidad</label><input id="oc-cantidad" v-model.number="cantidad" type="number" min="0.000001" step="any" :disabled="!puedeEditar" /></div>
      <div class="purchase-field"><label for="oc-codigo">Código o código de barras</label><div class="purchase-search-input"><ScanLine :size="18" /><input id="oc-codigo" v-model="busqueda" :disabled="!puedeEditar" placeholder="Escanea o escribe el código del producto" @keydown.enter.prevent="agregarCodigo" /></div></div>
      <button class="btn-primary purchase-btn purchase-btn--square" :disabled="!puedeEditar" title="Agregar por código" aria-label="Agregar por código" @click="agregarCodigo"><Plus :size="18" /></button>
      <button class="purchase-btn purchase-btn--secondary" :disabled="!puedeEditar" @click="buscador.showModal()"><Search :size="17" />Buscar</button>
      <button class="purchase-btn purchase-btn--soft" :disabled="!puedeEditar" @click="verProducto=true"><PackagePlus :size="17" />Nuevo producto</button>
     </div>
     <Tabla :columns="columnasDetalle" :rows="detalleActivo" row-key="producto_id" empty-text="Aún no has agregado productos a esta orden." class="purchase-table [&_th]:px-4! [&_th]:py-2! [&_td]:px-4! [&_td]:py-2! [&_td]:text-xs">
      <template #cell-codigo="{value}"><span class="purchase-code">{{value || 'S/C'}}</span></template>
      <template #cell-nombre_producto="{value}"><div class="purchase-product"><span><Boxes :size="16" /></span><strong :title="value">{{value}}</strong></div></template>
      <template #cell-nombre_um="{value}"><span class="text-muted">{{value || '—'}}</span></template>
      <template #cell-fecha_vence="{row}"><input v-if="Number(row.control_vence)===1" v-model="row.fecha_vence" type="date" :disabled="!puedeEditar" class="purchase-table-input min-w-34" :aria-label="'Vencimiento de '+row.nombre_producto" /><span v-else class="text-muted">No aplica</span></template>
      <template #cell-cantidad="{row}"><input v-model.number="row.cantidad" type="number" min="0.000001" step="any" :disabled="!puedeEditar" class="purchase-table-input w-22" :aria-label="'Cantidad de '+row.nombre_producto" /></template>
      <template #cell-precio_costo="{row}"><input v-model.number="row.precio_costo" type="number" min="0" step="any" :disabled="!puedeEditar" class="purchase-table-input w-28" :aria-label="'Costo de '+row.nombre_producto" /></template>
      <template #cell-total="{row}"><strong class="whitespace-nowrap tabular-nums text-ink">{{monto(totalLinea(row))}}</strong></template>
      <template #cell-quitar="{row}"><button v-if="editable" class="purchase-delete" :disabled="!puedeEditar" :aria-label="'Quitar '+row.nombre_producto" title="Quitar producto" @click="quitar(row)"><Trash2 :size="16" /></button></template>
     </Tabla>
    </section>
   </div>

   <aside class="purchase-aside">
    <section class="purchase-summary">
     <div class="purchase-summary-head"><div><span>Resumen de orden</span><strong>{{compra?.numero || 'Sin número'}}</strong></div><ShoppingCart :size="22" /></div>
     <div class="purchase-progress" aria-label="Estado de la orden"><div class="active"><span><Check :size="13" /></span><small>Creada</small></div><i :class="{'active':estadoId===2}"></i><div :class="{'active':estadoId===2}"><span><Check v-if="estadoId===2" :size="13" /><span v-else>2</span></span><small>Recibida</small></div></div>
     <dl class="purchase-totals"><div><dt>Productos distintos</dt><dd>{{detalleActivo.length}}</dd></div><div><dt>Unidades totales</dt><dd>{{totalUnidades.toLocaleString('es-GT')}}</dd></div><div><dt>Estado</dt><dd><span :class="estadoClase"><span></span>{{estadoNombre}}</span></dd></div></dl>
     <div class="purchase-grand-total"><span>Total de la compra</span><strong>{{monto(compra && estadoId===3 ? compra.total_costo : total)}}</strong><small>Importe calculado según el detalle</small></div>
     <button v-if="editable" class="btn-primary purchase-btn w-full" :disabled="!puedeEditar" @click="solicitar('guardar')"><LoaderCircle v-if="ocupado" :size="17" class="animate-spin" /><Save v-else :size="17" />{{ocupado?'Guardando…':'Guardar'}}</button>
    </section>
    <section class="purchase-card purchase-notes"><div class="purchase-section-title"><span><FileText :size="18" /></span><div><h2>Referencias</h2><p>Notas internas de la compra</p></div></div><textarea id="oc-referencias" v-model="formulario.referencias" :disabled="!puedeEditar" rows="5" placeholder="Añade instrucciones, números de cotización u observaciones…"></textarea><small>{{formulario.referencias.length}} caracteres</small></section>
   </aside>
  </div>

  <dialog ref="buscador" class="purchase-dialog purchase-dialog--wide" aria-labelledby="titulo-buscar">
   <header><div class="purchase-section-title"><span><Search :size="18" /></span><div><h2 id="titulo-buscar">Buscar productos</h2><p>Selecciona un producto para agregarlo a la orden</p></div></div><button class="purchase-close" aria-label="Cerrar búsqueda" @click="buscador.close()"><X :size="19" /></button></header>
   <div class="purchase-dialog-filters"><div class="purchase-search-input"><Search :size="17" /><input v-model="filtros.termino" placeholder="Código, nombre o marca" aria-label="Buscar productos" /></div><select v-model="filtros.categoria_id" aria-label="Filtrar categoría"><option value="">Todas las categorías</option><option v-for="item in catalogos.categorias" :key="item.id" :value="item.id">{{item.nombre}}</option></select><select v-model="filtros.marca_id" aria-label="Filtrar marca"><option value="">Todas las marcas</option><option v-for="item in catalogos.marcas" :key="item.id" :value="item.id">{{item.nombre}}</option></select></div>
   <Tabla :columns="columnasProductos" :rows="productos" class="[&_td]:px-4! [&_th]:px-4! [&_th]:py-2! [&_td]:py-2!"><template #cell-codigo="{value}"><span class="purchase-code">{{value}}</span></template><template #cell-nombre="{value}"><div class="purchase-product"><span><Boxes :size="16" /></span><strong>{{value}}</strong></div></template><template #cell-costo="{value}"><strong class="tabular-nums">{{monto(value)}}</strong></template><template #cell-agregar="{row}"><button class="purchase-add-product" :aria-label="'Agregar '+row.nombre" title="Agregar producto" @click="agregar(row) && toast.success('Producto agregado.')"><Plus :size="17" />Agregar</button></template></Tabla>
  </dialog>
  <FormProducto v-if="verProducto" @cerrar="verProducto=false" @guardada="productoCreado" />
  <dialog ref="confirmacion" class="purchase-dialog purchase-dialog--confirm" aria-labelledby="titulo-confirmar" @cancel.prevent="!ocupado && confirmacion.close()">
   <div class="purchase-confirm-icon" :class="{'purchase-confirm-icon--danger':accion==='anular'}"><Ban v-if="accion==='anular'" :size="24" /><PackageCheck v-else-if="accion==='recibir'" :size="24" /><Save v-else :size="24" /></div>
   <h3 id="titulo-confirmar">{{accion==='guardar'?'¿Guardar orden?':accion==='recibir'?'¿Recibir orden?':accion==='anular'?'¿Anular orden?':'¿Salir sin guardar?'}}</h3><p>{{accion==='recibir'?'Se registrará la entrada de todos los productos al inventario.':accion==='anular'?'La orden quedará anulada y los cambios sin guardar se descartarán.':accion==='salir'?'Tienes cambios pendientes que se perderán.':'Verifica que los datos y productos sean correctos.'}}</p>
   <div class="purchase-confirm-actions"><button class="purchase-btn purchase-btn--secondary" :disabled="ocupado" @click="confirmacion.close()">Cancelar</button><button class="purchase-btn" :class="accion==='anular'?'purchase-btn--danger-solid':'btn-primary'" :disabled="ocupado" @click="ejecutar"><LoaderCircle v-if="ocupado" :size="16" class="animate-spin" />{{ocupado?'Procesando…':'Confirmar'}}</button></div>
  </dialog>
 </div>
</template>

<style scoped>
.purchase-page{width:100%;max-width:1680px;margin:0 auto;padding:1.5rem}.purchase-header{display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:1.5rem}.purchase-back{display:inline-flex;align-items:center;gap:.45rem;color:var(--muted);font-size:.75rem;font-weight:600}.purchase-back:hover{color:var(--accent)}.purchase-back span{color:var(--line)}.purchase-header h1{font-size:1.4rem;line-height:1.75rem;font-weight:700;letter-spacing:-.02em}.purchase-header p{margin-top:.2rem;color:var(--muted);font-size:.8rem}.purchase-header-status{margin-top:.2rem}.purchase-title-icon{display:inline-flex;width:auto;height:auto;place-items:center;border:0;border-radius:0;background:transparent;color:var(--accent)}.purchase-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.55rem}.purchase-btn{display:inline-flex;min-height:2.55rem;align-items:center;justify-content:center;gap:.5rem;border:1px solid transparent;border-radius:.55rem;padding:.58rem .9rem;font-size:.8rem;font-weight:650;transition:.16s ease}.purchase-btn:disabled{opacity:.5}.purchase-btn--primary{border-color:#2563eb;background:#2563eb;color:#fff;box-shadow:0 4px 10px rgba(37,99,235,.18)}.purchase-btn--primary:hover:not(:disabled){background:#1d4ed8}.purchase-btn--secondary{border-color:var(--line);background:var(--surface);color:var(--ink)}.purchase-btn--secondary:hover:not(:disabled){background:var(--soft)}.purchase-btn--soft{border-color:color-mix(in srgb,#2563eb 18%,transparent);background:color-mix(in srgb,#2563eb 8%,transparent);color:#2563eb}.purchase-btn--success{border-color:color-mix(in srgb,#059669 28%,transparent);background:color-mix(in srgb,#059669 8%,var(--surface));color:#047857}.purchase-btn--danger{border-color:color-mix(in srgb,#dc2626 20%,transparent);background:var(--surface);color:#dc2626}.purchase-btn--danger-solid{background:#dc2626;color:#fff}.purchase-btn--square{width:2.6rem;padding:0}.purchase-layout{display:grid;grid-template-columns:minmax(0,1fr) 19rem;gap:1.25rem;align-items:start}.purchase-card,.purchase-summary{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:.8rem;background:var(--surface);box-shadow:0 1px 2px rgba(15,23,42,.025)}.purchase-card-header{display:flex;min-height:4.75rem;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding:1rem 1.25rem}.purchase-section-title{display:flex;align-items:center;gap:.75rem}.purchase-section-title>span{display:grid;width:2.25rem;height:2.25rem;flex:none;place-items:center;border-radius:.55rem;background:color-mix(in srgb,#2563eb 9%,var(--surface));color:#2563eb}.purchase-section-title h2{font-size:.9rem;font-weight:700}.purchase-section-title p{margin-top:.15rem;color:var(--muted);font-size:.72rem}.required-note,.purchase-count{color:var(--muted);font-size:.7rem}.required-note b{color:#dc2626}.purchase-count{border-radius:999px;background:var(--soft);padding:.3rem .65rem;font-weight:600}.purchase-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.1rem;padding:1.25rem}.purchase-field label{display:flex;align-items:center;gap:.4rem;margin-bottom:.45rem;color:var(--muted);font-size:.71rem;font-weight:650}.purchase-field label b{color:#dc2626}.purchase-field input,.purchase-field select,.purchase-dialog-filters input,.purchase-dialog-filters select{width:100%;min-height:2.65rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.55rem .75rem;color:var(--ink);font-size:.8rem;outline:none;transition:.15s}.purchase-field input:focus,.purchase-field select:focus,.purchase-dialog-filters input:focus,.purchase-dialog-filters select:focus,.purchase-notes textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}.purchase-field input:disabled,.purchase-field select:disabled{background:var(--soft)}.purchase-addbar{display:grid;grid-template-columns:5.25rem minmax(12rem,1fr) auto auto auto;align-items:end;gap:.6rem;padding:1rem 1.25rem;background:color-mix(in srgb,var(--soft) 45%,var(--surface))}.purchase-search-input{position:relative}.purchase-search-input>svg{position:absolute;left:.75rem;top:50%;z-index:1;transform:translateY(-50%);color:var(--muted)}.purchase-search-input input{padding-left:2.3rem!important}.purchase-card--table :deep(thead th){background:color-mix(in srgb,var(--soft) 75%,var(--surface));color:var(--muted);font-size:.67rem;font-weight:700;letter-spacing:.035em;text-transform:uppercase}.purchase-card--table :deep(tbody tr:hover){background:color-mix(in srgb,#2563eb 3%,var(--surface))}.purchase-code{display:inline-flex;border-radius:.35rem;background:var(--soft);padding:.25rem .45rem;color:var(--muted);font-family:ui-monospace,SFMono-Regular,monospace;font-size:.68rem;font-weight:700}.purchase-product{display:flex;min-width:11rem;align-items:center;gap:.65rem}.purchase-product>span{display:grid;width:2rem;height:2rem;flex:none;place-items:center;border-radius:.45rem;background:color-mix(in srgb,#2563eb 8%,var(--surface));color:#2563eb}.purchase-product strong{max-width:13rem;overflow:hidden;text-overflow:ellipsis}.purchase-table-input{min-height:2.25rem;border:1px solid var(--line);border-radius:.42rem;background:var(--surface);padding:.35rem .55rem;color:var(--ink);font-size:.75rem;outline:none}.purchase-table-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}.purchase-delete{display:grid;width:2rem;height:2rem;place-items:center;border-radius:.45rem;color:#dc2626}.purchase-delete:hover{background:rgba(220,38,38,.08)}.purchase-aside{position:sticky;top:6rem;display:grid;gap:1.25rem}.purchase-summary{padding:1.25rem}.purchase-summary-head{display:flex;align-items:center;justify-content:space-between;color:#2563eb}.purchase-summary-head div{display:grid;gap:.2rem}.purchase-summary-head span{color:var(--muted);font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}.purchase-summary-head strong{color:var(--ink);font-size:.95rem}.purchase-progress{display:flex;align-items:flex-start;margin:1.3rem 0}.purchase-progress>div{display:grid;flex:none;place-items:center;gap:.35rem;color:var(--muted)}.purchase-progress>div>span{display:grid;width:1.65rem;height:1.65rem;place-items:center;border:2px solid var(--line);border-radius:50%;background:var(--surface);font-size:.65rem;font-weight:700}.purchase-progress>div.active>span{border-color:#2563eb;background:#2563eb;color:#fff}.purchase-progress small{font-size:.62rem}.purchase-progress>i{height:2px;flex:1;margin-top:.78rem;background:var(--line)}.purchase-progress>i.active{background:#2563eb}.purchase-totals{display:grid;gap:.8rem;border-top:1px solid var(--line);padding:1.1rem 0}.purchase-totals>div{display:flex;align-items:center;justify-content:space-between;gap:1rem}.purchase-totals dt{color:var(--muted);font-size:.76rem}.purchase-totals dd{font-size:.78rem;font-weight:700}.purchase-status{display:inline-flex;align-items:center;gap:.38rem;border-radius:999px;padding:.32rem .6rem;font-size:.66rem;font-weight:700}.purchase-status>span{width:.38rem;height:.38rem;border-radius:50%;background:currentColor}.purchase-status--primary{background:rgba(37,99,235,.09);color:#2563eb}.purchase-status--success{background:rgba(5,150,105,.1);color:#047857}.purchase-status--danger{background:rgba(220,38,38,.09);color:#dc2626}.purchase-grand-total{display:grid;gap:.25rem;margin:0 -1.25rem 1rem;padding:1.1rem 1.25rem;border-block:1px solid var(--line);background:color-mix(in srgb,#2563eb 4%,var(--surface))}.purchase-grand-total span{color:var(--muted);font-size:.72rem}.purchase-grand-total strong{overflow-wrap:anywhere;color:#2563eb;font-size:1.55rem;line-height:1.8rem;letter-spacing:-.025em}.purchase-grand-total small{color:var(--muted);font-size:.62rem}.purchase-notes{padding:1.1rem}.purchase-notes textarea{width:100%;margin-top:1rem;resize:vertical;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.7rem;color:var(--ink);font-size:.78rem;outline:none}.purchase-notes>small{display:block;margin-top:.35rem;text-align:right;color:var(--muted);font-size:.62rem}.purchase-loading,.purchase-error{display:flex;min-height:18rem;align-items:center;justify-content:center;gap:1rem;border:1px solid var(--line);border-radius:.8rem;background:var(--surface)}.purchase-loading div{display:grid}.purchase-loading span,.purchase-error p{color:var(--muted);font-size:.78rem}.purchase-error{flex-direction:column;text-align:center;color:#dc2626}.purchase-error strong{color:var(--ink)}.purchase-dialog{margin:auto;max-height:calc(100dvh - 2rem);overflow:auto;border:1px solid var(--line);border-radius:.8rem;background:var(--surface);padding:0;color:var(--ink);box-shadow:0 24px 70px rgba(15,23,42,.28)}.purchase-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.purchase-dialog--wide{width:calc(100% - 2rem);max-width:70rem}.purchase-dialog>header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);padding:1rem 1.25rem}.purchase-close{display:grid;width:2.25rem;height:2.25rem;place-items:center;border-radius:.5rem;color:var(--muted)}.purchase-close:hover{background:var(--soft)}.purchase-dialog-filters{display:grid;grid-template-columns:minmax(12rem,1fr) 13rem 13rem;gap:.75rem;padding:1rem 1.25rem}.purchase-add-product{display:inline-flex;align-items:center;gap:.35rem;border-radius:.45rem;background:rgba(37,99,235,.09);padding:.45rem .65rem;color:#2563eb;font-size:.72rem;font-weight:700}.purchase-dialog--confirm{width:calc(100% - 2rem);max-width:25rem;padding:1.5rem;text-align:center}.purchase-confirm-icon{display:grid;width:3.25rem;height:3.25rem;margin:0 auto 1rem;place-items:center;border-radius:50%;background:rgba(37,99,235,.1);color:#2563eb}.purchase-confirm-icon--danger{background:rgba(220,38,38,.09);color:#dc2626}.purchase-dialog--confirm h3{font-size:1.05rem;font-weight:700}.purchase-dialog--confirm p{margin-top:.45rem;color:var(--muted);font-size:.78rem;line-height:1.35rem}.purchase-confirm-actions{display:flex;justify-content:center;gap:.6rem;margin-top:1.25rem}
.purchase-header{margin-bottom:1rem}.purchase-title-icon{display:grid;width:2.75rem;height:2.75rem;place-items:center;border:1px solid var(--line);border-radius:.75rem;background:color-mix(in srgb,var(--accent) 7%,var(--surface));color:var(--accent)}
@media(max-width:1280px){.purchase-layout{grid-template-columns:minmax(0,1fr) 17rem}.purchase-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.purchase-addbar{grid-template-columns:5.25rem minmax(10rem,1fr) auto auto}.purchase-addbar .purchase-btn--soft{grid-column:2/-1;justify-self:end}}
@media(max-width:960px){.purchase-header{align-items:flex-start;flex-direction:column}.purchase-actions{justify-content:flex-start}.purchase-layout{grid-template-columns:1fr}.purchase-aside{position:static;grid-template-columns:1fr 1fr}.purchase-summary{grid-row:span 2}}
@media(max-width:700px){.purchase-page{padding:1rem}.purchase-fields{grid-template-columns:1fr}.purchase-addbar{grid-template-columns:5rem minmax(0,1fr)}.purchase-addbar .purchase-btn{width:100%}.purchase-addbar .purchase-btn--square{width:100%}.purchase-addbar .purchase-btn--soft{grid-column:auto}.purchase-aside{grid-template-columns:1fr}.purchase-summary{grid-row:auto}.purchase-dialog-filters{grid-template-columns:1fr}.purchase-actions{width:100%}.purchase-actions .purchase-btn{flex:1}.required-note{display:none}}
</style>
