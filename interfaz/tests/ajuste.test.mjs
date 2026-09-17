import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { ref, reactive, computed } from 'vue'

const fuente = fs.readFileSync(new URL('../src/views/inventario/ajuste/Form.vue', import.meta.url), 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/^import .*$/gm, '')

const fuenteLista = fs.readFileSync(new URL('../src/views/inventario/ajuste/Principal.vue', import.meta.url), 'utf8')
  .split('<script setup>')[1]
  .split('</script>')[0]
  .replace(/^import .*$/gm, '')

const producto = {
  id: 10,
  codigo: 'P-10',
  codigo_barra: '1010',
  nombre: 'Producto de prueba',
  unidad_medida_id: 2,
  nombre_um: 'Unidad',
  control_vence: 0,
}

const catalogos = {
  tipos: [
    { id: 1, nombre: 'Sobrante', naturaleza: 'POSITIVO', requiere_observacion: 0 },
    { id: 2, nombre: 'Merma', naturaleza: 'NEGATIVO', requiere_observacion: 1 },
  ],
  estados: [],
  sucursales: [{ id: 3, nombre: 'Central' }],
  productos: [producto],
  existencias: [{ producto_id: 10, unidad_medida_id: 2, sucursal_id: 3, cantidad: 5 }],
}

function preparar(ajuste = null) {
  const peticiones = []
  const eventos = []
  const avisos = []
  const contexto = vm.createContext({
    ref,
    reactive,
    computed,
    onMounted: () => {},
    defineProps: () => ({ ajuste }),
    defineEmits: () => (...args) => eventos.push(args),
    toast: {
      error: mensaje => avisos.push(mensaje),
      info: mensaje => avisos.push(mensaje),
      success: () => {},
    },
    api: {
      get: async url => ({
        data: url.includes('get_datos')
          ? { cat: catalogos }
          : { lista: [{ ...producto, producto_id: 10, nombre_producto: producto.nombre, cantidad: 2, observacion: '' }] },
      }),
      post: async (url, datos) => {
        peticiones.push({ url, datos })
        return { data: { exito: 1, linea: { id: ajuste?.id || 8 } } }
      },
    },
  })
  vm.runInContext(fuente, contexto)
  vm.runInContext('confirmacion.value={showModal(){},close(){}};buscador.value={showModal(){},close(){}}', contexto)
  return { contexto, peticiones, eventos, avisos, ejecutar: codigo => vm.runInContext(codigo, contexto) }
}

const nuevo = preparar()
await nuevo.ejecutar('cargar()')
nuevo.ejecutar('formulario.inventario_ajuste_tipo_id=1;formulario.sucursal_id=3;cantidad.value=2;agregar(catalogos.value.productos[0]);agregar(catalogos.value.productos[0])')
assert.equal(nuevo.ejecutar('formulario.detalle.length'), 1)
assert.equal(nuevo.ejecutar('formulario.detalle[0].cantidad'), 3)
assert.equal(nuevo.ejecutar('validar()'), true)
nuevo.ejecutar('solicitar("guardar")')
await nuevo.ejecutar('ejecutar()')
assert.equal(nuevo.peticiones[0].url, 'index.php/inventario/ajuste/guardar/')
assert.equal(nuevo.peticiones[0].datos.detalle[0].cantidad, 3)
assert.equal(nuevo.eventos[0][0], 'actualizar')

const negativo = preparar()
await negativo.ejecutar('cargar()')
negativo.ejecutar('formulario.inventario_ajuste_tipo_id=2;formulario.sucursal_id=3;formulario.motivo="Merma comprobada";cantidad.value=6;agregar(catalogos.value.productos[0])')
assert.equal(negativo.ejecutar('esNegativo.value'), true)
assert.equal(negativo.ejecutar('validar()'), false)
assert.match(negativo.avisos.at(-1), /supera el stock/)
negativo.ejecutar('formulario.detalle[0].cantidad=5')
assert.equal(negativo.ejecutar('validar()'), true)

const borrador = preparar({ id: 9, codigo_estado: 'BORRADOR', inventario_ajuste_tipo_id: 1, sucursal_id: 3 })
await borrador.ejecutar('cargar()')
borrador.ejecutar('solicitar("aplicar")')
await borrador.ejecutar('ejecutar()')
assert.equal(borrador.peticiones[0].url, 'index.php/inventario/ajuste/aplicar/9')

const aplicado = preparar({ id: 9, codigo_estado: 'APLICADO', inventario_ajuste_tipo_id: 1, sucursal_id: 3 })
await aplicado.ejecutar('cargar()')
assert.equal(aplicado.ejecutar('puedeEditar.value'), false)
aplicado.ejecutar('solicitar("anular")')
await aplicado.ejecutar('ejecutar()')
assert.equal(aplicado.peticiones[0].url, 'index.php/inventario/ajuste/anular/9')

const borradorAnulado = preparar({ id: 11, codigo_estado: 'BORRADOR', inventario_ajuste_tipo_id: 1, sucursal_id: 3 })
await borradorAnulado.ejecutar('cargar()')
borradorAnulado.ejecutar('solicitar("anular")')
await borradorAnulado.ejecutar('ejecutar()')
assert.equal(borradorAnulado.peticiones[0].url, 'index.php/inventario/ajuste/anular/11')

const consultasLista = []
const contextoLista = vm.createContext({
  ref,
  reactive,
  onMounted: () => {},
  toast: { error: () => {} },
  api: { get: async (...args) => consultasLista.push(args) },
})
vm.runInContext(fuenteLista, contextoLista)
const ejecutarLista = codigo => vm.runInContext(codigo, contextoLista)
ejecutarLista(`
  filtros.fecha_desde='2026-09-01';
  filtros.fecha_hasta='2026-09-30';
  lista.value=[{id:1,numero:'AJ-1',fecha:'2026-09-10 10:00:00',codigo_estado:'BORRADOR'}];
  actualizar({id:1,numero:'AJ-1',fecha:'2026-09-10 10:00:00',codigo_estado:'APLICADO'});
`)
assert.equal(ejecutarLista('lista.value.length'), 1)
assert.equal(ejecutarLista('lista.value[0].codigo_estado'), 'APLICADO')
assert.equal(consultasLista.length, 0)

ejecutarLista(`
  filtros.inventario_ajuste_estado_id=1;
  lista.value[0].inventario_ajuste_estado_id=1;
  actualizar({id:1,numero:'AJ-1',fecha:'2026-09-10 10:00:00',inventario_ajuste_estado_id:2,codigo_estado:'APLICADO'});
`)
assert.equal(ejecutarLista('lista.value.length'), 0)

ejecutarLista(`
  filtros.inventario_ajuste_estado_id='';
  actualizar({id:2,numero:'AJ-2',fecha:'2026-09-11 10:00:00',codigo_estado:'BORRADOR'});
`)
assert.equal(ejecutarLista('lista.value[0].id'), 2)
assert.equal(consultasLista.length, 0)

console.log('Ajustes: operaciones, validaciones y actualización local del listado correctas (API simulada).')
