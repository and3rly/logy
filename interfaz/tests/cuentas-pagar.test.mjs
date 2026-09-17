import fs from 'node:fs'
import vm from 'node:vm'
import assert from 'node:assert/strict'
import { computed, reactive, ref, watch } from 'vue'

const extraer = ruta => fs.readFileSync(new URL(ruta, import.meta.url), 'utf8')
  .split('<script setup>')[1].split('</script>')[0].replace(/^import .*$/gm, '')
const fuenteNueva = extraer('../src/views/cxp/NuevaCuenta.vue')
const fuentePago = extraer('../src/views/cxp/PagarCuenta.vue')
const catalogos = {
  proveedores: [{ id: 1, nombre: 'Proveedor prueba', identificacion: '123', credito_dias: 30 }],
  monedas: [{ id: 1, nombre: 'Quetzal', codigo: 'GTQ', simbolo: 'Q' }],
  formas_pago: [{ id: 2, nombre: 'Transferencia' }],
}

{
  const peticiones = [], eventos = []
  const contexto = vm.createContext({ ref, reactive, computed, watch, onMounted: () => {},
    defineProps: () => ({ catalogos }), defineEmits: () => (...args) => eventos.push(args),
    toast: { error: () => {}, success: () => {} }, Date,
    api: { post: async (url, datos) => { peticiones.push({ url, datos }); return { data: { exito: 1, mensaje: 'ok', linea: { id: 7 } } } } },
  })
  vm.runInContext(fuenteNueva, contexto)
  await vm.runInContext(`formulario.proveedor_id=1;formulario.moneda_id=1;formulario.factura_numero='FAC-1';formulario.total=250;guardar()`, contexto)
  assert.equal(peticiones[0].url, 'index.php/cxp/cuenta_pagar/guardar')
  assert.equal(peticiones[0].datos.total, 250)
  assert.equal(eventos[0][0], 'guardada')
}

{
  const cuenta = { id: 8, factura_numero: 'FAC-2', nombre_proveedor: 'Proveedor prueba', total: 300, abono: 100, saldo: 200, simbolo_moneda: 'Q' }
  const peticiones = [], eventos = [], avisos = []
  const contexto = vm.createContext({ ref, reactive, onMounted: () => {},
    defineProps: () => ({ cuenta, catalogos }), defineEmits: () => (...args) => eventos.push(args),
    toast: { error: mensaje => avisos.push(mensaje), success: () => {} }, Date,
    api: { get: async () => ({ data: { lista: [] } }), post: async (url, datos) => { peticiones.push({ url, datos }); return { data: { exito: 1, mensaje: 'ok', linea: { ...cuenta, saldo: 150 }, pago: { id: 4, comprobante_numero: 'EGR-2026-000004' } } } } },
  })
  vm.runInContext(fuentePago, contexto)
  await vm.runInContext(`formulario.forma_pago_id=2;formulario.total=50;pagar()`, contexto)
  assert.equal(peticiones[0].url, 'index.php/cxp/cuenta_pagar/pagar/8')
  assert.equal(peticiones[0].datos.total, 50)
  assert.deepEqual(eventos.map(evento => evento[0]), ['actualizada', 'imprimir', 'cerrar'])
  vm.runInContext(`formulario.total=201`, contexto); await vm.runInContext('pagar()', contexto)
  assert.equal(peticiones.length, 1); assert.match(avisos.at(-1), /no superar el saldo/)
}

console.log('Cuentas por pagar: alta, pago parcial y bloqueo de sobrepago correctos (API simulada).')
