import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { ref, reactive, computed, watch, nextTick } from 'vue'

// Contratos de frontend con API simulada; no modifica datos del servidor.
for (const modulo of ['mnt/proveedor', 'mnt/cliente', 'mnt/sucursal', 'usuario', 'mnt/producto']) {
  for (const editar of [false, true]) {
    let enviado, evento
    const avisos = []
    const original = { id: 2, nombre: 'Prueba', alias: 'prueba', rol_id: 1, departamento_id: 1, municipio_id: 11, codigo: 'P1', marca_id: 1, categoria_id: 1, unidad_medida_id: 1, tipo_producto: 'B', activo: 1 }
    const catalogos = { roles: [{ id: 1, nombre: 'Rol' }], departamentos: [{ id: 1, nombre: 'D1' }, { id: 2, nombre: 'D2' }], municipios: [{ id: 11, nombre: 'M1', departamento_id: 1 }, { id: 22, nombre: 'M2', departamento_id: 2 }], tipos: ['B', 'S'], marcas: [], unidades: [], categorias: [] }
    const contexto = vm.createContext({
      ref, reactive, computed, watch, onMounted: () => {}, onBeforeUnmount: () => {},
      defineProps: () => ({ registro: editar ? original : null }),
      defineEmits: () => (...args) => { evento = args },
      toast: { error: mensaje => avisos.push(mensaje) }, FormData, URL,
      api: {
        get: async () => ({ data: { cat: catalogos } }),
        post: async (url, datos, config) => {
          enviado = { url, datos, config }
          return { data: { exito: 1, linea: { id: 2, nombre: 'Prueba' } } }
        },
      },
    })
    const fuente = fs.readFileSync(new URL('../src/views/' + modulo + '/Form.vue', import.meta.url), 'utf8').split('<script setup>')[1].split('</script>')[0].replace(/^import .*$/gm, '')
    vm.runInContext(fuente, contexto)
    if (modulo !== 'mnt/proveedor') await vm.runInContext('cargarCatalogos()', contexto)
    if (!editar) {
      vm.runInContext('Object.assign(formulario, {nombre:"Prueba",alias:"prueba",clave:"Temporal123",rol_id:1,codigo:"P1",marca_id:1,categoria_id:1,unidad_medida_id:1,departamento_id:1})', contexto)
      await nextTick()
      vm.runInContext('formulario.municipio_id=11', contexto)
    }
    await vm.runInContext('guardar()', contexto)
    assert.equal(avisos.length, 0, modulo + JSON.stringify(avisos))
    assert.equal(evento[0], 'guardada')
    assert.equal(enviado.url, 'index.php/' + modulo + '/guardar/' + (editar ? '2' : ''))
    if (modulo === 'usuario') assert.equal('clave' in enviado.datos, !editar)
    if (modulo === 'mnt/producto') {
      assert.ok(enviado.datos instanceof FormData)
      assert.equal(enviado.datos.get('codigo'), 'P1')
      assert.equal(enviado.config.headers['Content-Type'], undefined)
      assert.equal(enviado.datos.has('foto'), false)
    }
    if (modulo === 'mnt/sucursal' || modulo === 'mnt/cliente') {
      vm.runInContext('formulario.departamento_id=2', contexto)
      await nextTick()
      assert.equal(vm.runInContext('formulario.municipio_id', contexto), null)
      assert.equal(vm.runInContext('opciones(campos.find(c=>c.clave==="municipio_id"))[0].valor', contexto), 22)
    }
  }
  console.log(modulo + ': alta, edición y contrato API correctos (simulados)')
}

for (const editar of [false, true]) {
  let enviado, evento
  const avisos = []
  const contexto = vm.createContext({
    ref, reactive,
    onMounted: () => {},
    defineProps: () => ({ registro: editar ? { id: 7, nombre: 'Supervisor', activo: 1 } : null }),
    defineEmits: () => (...args) => { evento = args },
    toast: { error: mensaje => avisos.push(mensaje) },
    api: {
      post: async (url, datos) => {
        enviado = { url, datos }
        return { data: { exito: 1, linea: { id: 7, ...datos } } }
      },
    },
  })
  const fuente = fs.readFileSync(new URL('../src/views/mnt/rol/Form.vue', import.meta.url), 'utf8').split('<script setup>')[1].split('</script>')[0].replace(/^import .*$/gm, '')
  vm.runInContext(fuente, contexto)
  if (!editar) vm.runInContext('formulario.nombre="Supervisor"', contexto)
  await vm.runInContext('guardar()', contexto)
  assert.equal(avisos.length, 0, JSON.stringify(avisos))
  assert.equal(evento[0], 'guardada')
  assert.equal(enviado.url, 'index.php/mnt/rol/guardar/' + (editar ? '7' : ''))
  assert.deepEqual({ ...enviado.datos }, { nombre: 'Supervisor', activo: 1 })
}
console.log('mnt/rol: alta, edición y contrato API correctos (simulados)')
