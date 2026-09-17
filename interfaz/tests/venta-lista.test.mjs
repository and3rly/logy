import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { computed, reactive, ref } from 'vue'

const fuente = fs.readFileSync(new URL('../src/views/venta/Lista.vue', import.meta.url), 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/import[\s\S]*?from\s+['"][^'"]+['"]\s*/g, '')

const peticiones = []
const avisos = []
const venta = { id: 8, fecha: '2026-09-16 10:15:00', correlativo: 'FAC-000000001', nombre_estado: 'Facturada', nombre_cliente: 'Cliente prueba', nombre_sucursal: 'Central', nombre_forma_pago: 'Contado', simbolo_moneda: 'Q', total_precio: 25, base: 25 }
const linea = { id: 1, nombre_producto: 'Artículo', codigo: 'A-1', nombre_unidad: 'Unidad', cantidad: 2, precio: 12.5, base: 25 }

const contexto = vm.createContext({
  ref, reactive, computed,
  onMounted: () => {},
  toast: { error: mensaje => avisos.push(mensaje), info: mensaje => avisos.push(mensaje), success: mensaje => avisos.push(mensaje) },
  api: {
    get: async (url, opciones) => {
      peticiones.push({ url, opciones })
      if (url.includes('get_datos')) return { data: { cat: { estados: [{ id: 2, nombre: 'Facturada' }], sucursales: [{ id: 2, nombre: 'Central' }] } } }
      if (url.includes('/detalle/')) return { data: { lista: [linea] } }
      return { data: { lista: [venta] } }
    },
    post: async (url, datos) => {
      peticiones.push({ url, datos })
      return { data: { exito: 1, mensaje: 'Estado actualizado', linea: { ...venta, nombre_estado: 'Pagada' } } }
    },
  },
})

vm.runInContext(fuente, contexto)
const ejecutar = codigo => vm.runInContext(codigo, contexto)

await ejecutar('cargarCatalogos()')
await ejecutar('buscar()')
assert.equal(ejecutar('lista.value.length'), 1)
assert.equal(ejecutar('totalListado.value'), 25)
assert.equal(ejecutar('claseEstado("Facturada")'), 'sale-status--success')
assert.ok(peticiones.some(peticion => peticion.url.endsWith('/buscar')))

ejecutar('detalleDialog.value = { showModal(){}, close(){} }')
await ejecutar('verDetalle(lista.value[0])')
assert.equal(ejecutar('detalle.value[0].nombre_producto'), 'Artículo')
assert.ok(peticiones.some(peticion => peticion.url.endsWith('/detalle/8')))

ejecutar('accionDialog.value = { showModal(){}, close(){} }; solicitarAccion("pagar", lista.value[0])')
await ejecutar('ejecutarAccion()')
assert.ok(peticiones.some(peticion => peticion.url.endsWith('/pagar/8')))

console.log('Ventas: listado, filtros, totales y consulta de detalle correctos (API simulada).')
