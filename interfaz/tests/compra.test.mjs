import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { ref, reactive, computed } from 'vue'
const fuente = fs.readFileSync(new URL('../src/views/compra/Form.vue', import.meta.url), 'utf8').split('<script setup>')[1].split('</script>')[0].replace(/^import .*$/gm, '')
function preparar(compra = null, fallo = false) {
  const peticiones = [], eventos = [], avisos = []
  const producto = {id: 10, codigo: 'A', activo: 1, unidad_medida_id: 1, nombre: 'Artículo', costo: 2.5}
  const cat = {productos: [producto], proveedores: [], formas_pago: [], monedas: [], sucursales: [], categorias: [], marcas: []}
  const contexto = vm.createContext({
    ref, reactive, computed, onMounted: () => {},
    defineProps: () => ({compra}), defineEmits: () => (...args) => eventos.push(args),
    toast: {error: m => avisos.push(m), info: m => avisos.push(m), success: () => {}},
    api: {
      get: async url => {
        if (fallo) throw new Error('Error de carga')
        return {data: url.includes('get_datos') ? {cat} : {lista: [{id: 7, producto_id: 10, unidad_medida_id: 1, cantidad: 2, precio_costo: 2.5, anulado: 0}]}}
      },
      post: async (url, datos) => {peticiones.push({url, datos});return {data: {exito: 1, linea: {id: 4}}}},
    },
  })
  vm.runInContext(fuente, contexto)
  vm.runInContext('confirmacion.value={showModal(){},close(){}}', contexto)
  return {contexto, peticiones, eventos, avisos, ejecutar: texto => vm.runInContext(texto, contexto)}
}
const nueva = preparar()
await nueva.ejecutar('cargar()')
nueva.ejecutar('Object.assign(formulario,{proveedor_id:1,forma_pago_id:1,moneda_id:1,sucursal_id:1});cantidad.value=2;agregar(catalogos.value.productos[0]);agregar(catalogos.value.productos[0])')
assert.equal(nueva.ejecutar('detalleActivo.value.length'), 1)
assert.equal(nueva.ejecutar('total.value'), 7.5)
nueva.ejecutar('formulario.detalle[0].cantidad=-1')
assert.equal(nueva.ejecutar('validar()'), false)
nueva.ejecutar('formulario.detalle[0].cantidad=3;solicitar("guardar")')
await nueva.ejecutar('ejecutar()')
assert.equal(nueva.peticiones[0].url, 'index.php/compra/orden/guardar/')
assert.equal(nueva.peticiones[0].datos.detalle[0].total_costo, 7.5)
assert.equal(nueva.eventos[0][0], 'actualizar')
const existente = preparar({id: 4, compra_estado_id: 1, proveedor_id: 1, forma_pago_id: 1, moneda_id: 1, sucursal_id: 1})
await existente.ejecutar('cargar()')
assert.equal(existente.ejecutar('cambios.value'), false)
existente.ejecutar('formulario.detalle[0].cantidad=3;solicitar("recibir")')
assert.equal(existente.ejecutar('accion.value'), '')
existente.ejecutar('quitar(formulario.detalle[0])')
assert.equal(existente.ejecutar('formulario.detalle[0].anulado'), 1)
assert.equal(existente.ejecutar('total.value'), 0)
for (const tipo of ['recibir', 'anular']) {
  const orden = preparar({id: 4, compra_estado_id: 1})
  await orden.ejecutar('cargar()')
  orden.ejecutar('solicitar("' + tipo + '")')
  await orden.ejecutar('ejecutar()')
  assert.equal(orden.peticiones[0].url, 'index.php/compra/orden/' + tipo + '/4')
}
for (const estado of [2,3]) {
  const orden = preparar({id: 4, compra_estado_id: estado})
  await orden.ejecutar('cargar()')
  orden.ejecutar('agregar(catalogos.value.productos[0]);solicitar("guardar");solicitar("recibir");solicitar("anular")')
  assert.equal(orden.ejecutar('puedeEditar.value'), false)
  assert.equal(orden.ejecutar('formulario.detalle.length'), 1)
  assert.equal(orden.peticiones.length, 0)
}
const fallida = preparar(null, true)
await fallida.ejecutar('cargar()')
assert.equal(fallida.ejecutar('puedeEditar.value'), false)
console.log('Compras: totales, validación, bajas, guardado, recepción, anulación y bloqueos correctos (API simulada).')
