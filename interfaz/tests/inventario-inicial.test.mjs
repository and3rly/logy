import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { computed, reactive, ref } from 'vue'

const rutaFormulario = new URL('../src/views/inventario/inicial/Form.vue', import.meta.url)
const fuente = fs.readFileSync(rutaFormulario, 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/^import .*$/gm, '')
const fuenteLista = fs.readFileSync(new URL('../src/views/inventario/inicial/Principal.vue', import.meta.url), 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/^import .*$/gm, '')

assert.match(fuente, /new FormData\(\)/)
assert.match(fuente, /contenido\.append\('archivo', archivo\.value\)/)
assert.doesNotMatch(fuente, /exceljs|ExcelJS/)

const peticiones = []
const eventos = []
const contexto = vm.createContext({
  ref,
  reactive,
  computed,
  onMounted: () => {},
  defineProps: () => ({ inventario: { id: 9, codigo_estado: 'VALIDADO', sucursal_id: 3, archivo_nombre: 'carga.xlsx' } }),
  defineEmits: () => (...args) => eventos.push(args),
  toast: { error: () => {}, success: () => {} },
  api: {
    get: async url => ({ data: url.includes('get_datos')
      ? { cat: { estados: [], sucursales: [{ id: 3, nombre: 'Central' }] } }
      : { encabezado: { id: 9, codigo_estado: 'VALIDADO', sucursal_id: 3 }, lista: [] } }),
    post: async url => {
      peticiones.push(url)
      return { data: { exito: 1, mensaje: 'Procesado', linea: { id: 9, codigo_estado: 'PROCESADO' } } }
    },
  },
})
vm.runInContext(fuente, contexto)
vm.runInContext('confirmacion.value={showModal(){},close(){}}', contexto)
await vm.runInContext('cargar()', contexto)
vm.runInContext('solicitar("procesar")', contexto)
await vm.runInContext('ejecutar()', contexto)
assert.equal(peticiones[0], 'index.php/inventario/inventario_inicial/procesar/9')
assert.equal(eventos[0][0], 'actualizar')

const consultasLista = []
const contextoLista = vm.createContext({
  ref,
  reactive,
  onMounted: () => {},
  toast: { error: () => {} },
  api: { get: async (...args) => consultasLista.push(args) },
})
vm.runInContext(fuenteLista, contextoLista)
vm.runInContext(`
  filtros.fecha_desde='2026-09-01';
  filtros.fecha_hasta='2026-09-30';
  lista.value=[{id:1,numero:'INV-1',fecha:'2026-09-10 10:00:00',codigo_estado:'VALIDADO'}];
  actualizar({id:1,numero:'INV-1',fecha:'2026-09-10 10:00:00',codigo_estado:'PROCESADO'});
`, contextoLista)
assert.equal(vm.runInContext('lista.value[0].codigo_estado', contextoLista), 'PROCESADO')
assert.equal(consultasLista.length, 0)

console.log('Inventario inicial: carga multipart, operaciones y actualización local correctas (API simulada).')
