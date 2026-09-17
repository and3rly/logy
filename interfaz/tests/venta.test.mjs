import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { computed, reactive, ref } from 'vue'

const fuente = fs.readFileSync(new URL('../src/views/venta/Principal.vue', import.meta.url), 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/import[\s\S]*?from\s+['"][^'"]+['"]\s*/g, '')

const peticiones = []
const avisos = []
const catalogos = {
  series: [{ id: 1, nombre: 'Factura', codigo: 'FAC' }],
  estados: [{ id: 1, nombre: 'Creado' }],
  sucursales: [{ id: 2, nombre: 'Central' }],
  formas_pago: [{ id: 3, nombre: 'Contado' }],
  monedas: [{ id: 4, codigo: 'QTZ', simbolo: 'Q', nombre: 'Quetzal' }],
  categorias: [{ id: 5, nombre: 'General' }],
  clientes: [{ id: 6, nombre: 'Cliente prueba' }],
}
const listaProductos = [
  { id: 10, codigo: 'A', nombre: 'Artículo', tipo_producto: 'B', categoria_id: 5, existencia: 2, precio: 12.5 },
  { id: 11, codigo: 'S', nombre: 'Servicio', tipo_producto: 'S', categoria_id: 5, existencia: null, precio: 5 },
]

const contexto = vm.createContext({
  ref, reactive, computed,
  defineProps: () => ({ venta: null }),
  defineEmits: () => () => {},
  watch: () => {},
  onMounted: () => {},
  toast: {
    error: mensaje => avisos.push(mensaje),
    info: mensaje => avisos.push(mensaje),
    success: mensaje => avisos.push(mensaje),
  },
  api: {
    get: async url => url.includes('get_datos')
      ? { data: { cat: catalogos } }
      : { data: { lista: listaProductos } },
    post: async (url, datos) => {
      peticiones.push({ url, datos })
      return { data: { exito: 1, mensaje: 'Correcto', linea: { id: 55, correlativo: url.includes('facturar') ? 'FAC-001' : null } } }
    },
  },
})

vm.runInContext(fuente, contexto)
const ejecutar = codigo => vm.runInContext(codigo, contexto)

await ejecutar('cargarCatalogos()')
await ejecutar('cargarProductos()')
assert.equal(ejecutar('productos.value.length'), 2)
assert.equal(ejecutar('formulario.sucursal_id'), 2)

ejecutar('agregar(productos.value[0]); agregar(productos.value[0]); agregar(productos.value[0])')
assert.equal(ejecutar('carrito.value[0].cantidad'), 2)
assert.equal(ejecutar('subtotal.value'), 25)

ejecutar('agregar(productos.value[1]); cambiarCantidad(carrito.value[1], 1)')
assert.equal(ejecutar('totalUnidades.value'), 4)
assert.equal(ejecutar('subtotal.value'), 35)

ejecutar('datosVentaDialog.value = { abierto: false, showModal(){ this.abierto = true }, close(){ this.abierto = false } }; solicitar("guardar")')
assert.equal(ejecutar('datosVentaDialog.value.abierto'), true)
assert.equal(ejecutar('accionPendiente.value'), 'guardar')

await ejecutar('enviar("guardar")')
assert.equal(peticiones[0].url, 'index.php/venta/venta/guardar')
assert.equal(peticiones[0].datos.detalle.length, 2)
assert.equal(ejecutar('ventaId.value'), 55)
assert.equal(ejecutar('datosVentaDialog.value.abierto'), false)

await ejecutar('enviar("facturar")')
assert.equal(peticiones[1].url, 'index.php/venta/venta/facturar/55')
assert.equal(ejecutar('carrito.value.length'), 0)
assert.equal(ejecutar('ultimaVenta.value.correlativo'), 'FAC-001')

console.log('Ventas: catálogo, límites de existencia, carrito, guardado y facturación correctos (API simulada).')
