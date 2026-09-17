export const coloresCategoria = [
  { nombre: 'Gris', valor: 'secondary', color: '#64748b', texto: '#475569' },
  { nombre: 'Blanco', valor: 'light', color: '#cbd5e1', texto: '#475569' },
  { nombre: 'Negro', valor: 'dark', color: '#17253b', texto: '#17253b' },
  { nombre: 'Celeste', valor: 'info', color: '#06b6d4', texto: '#0e7490' },
  { nombre: 'Azul', valor: 'primary', color: '#2563eb', texto: '#1d4ed8' },
  { nombre: 'Morado', valor: 'purple', color: '#9333ea', texto: '#7e22ce' },
  { nombre: 'Índigo', valor: 'indigo', color: '#4f46e5', texto: '#4338ca' },
  { nombre: 'Rojo', valor: 'danger', color: '#dc2626', texto: '#b91c1c' },
  { nombre: 'Rosa', valor: 'pink', color: '#ec4899', texto: '#be185d' },
  { nombre: 'Naranja', valor: 'warning', color: '#f97316', texto: '#c2410c' },
  { nombre: 'Amarillo', valor: 'yellow', color: '#eab308', texto: '#a16207' },
  { nombre: 'Lima', valor: 'lime', color: '#84cc16', texto: '#4d7c0f' },
  { nombre: 'Verde Oscuro', valor: 'green', color: '#166534', texto: '#166534' },
  { nombre: 'Verde', valor: 'success', color: '#16a34a', texto: '#15803d' },
]

export function obtenerColorCategoria(etiqueta) {
  return coloresCategoria.find(color => color.valor === etiqueta)
    || coloresCategoria[0]
}

export function estiloEtiquetaCategoria(etiqueta) {
  const color = obtenerColorCategoria(etiqueta)
  return {
    '--category-color': color.color,
    backgroundColor: `${color.color}18`,
    borderColor: `${color.color}3d`,
    color: color.texto,
  }
}
