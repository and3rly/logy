<template>
	<div class="mb-3">
		<div>
			<ul class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">COMPRAS</a></li>
				<li class="breadcrumb-item">
					<a href="javascript:;" @click="$emit('cerrar')">ÓRDENES DE COMPRA</a>
				</li>
				<li class="breadcrumb-item active">{{ compra ? "EDITAR" : "NUEVA" }}</li>
			</ul>
			<h1 class="page-header mb-0">{{ compra ? "Editar compra" : "Nueva compra" }}</h1>
		</div>
		<div class="purchase-actions mt-3">
			<button type="button" class="purchase-action" @click="$emit('cerrar')">
				<i class="fas fa-arrow-left me-1"></i> Regresar
			</button>
			<a
				v-if="reg !== ''"
				:href="urlImpresion(reg)"
				target="_blank"
				rel="noopener"
				class="purchase-action"
			>
				<i class="fas fa-print me-1"></i> Imprimir
			</a>
			<button
				v-if="reg !== ''"
				type="button"
				class="purchase-action action-receive"
				:disabled="btnRecibir || Number(form.compra_estado_id) !== 1"
				@click="recibirOrden"
			>
				<i class="fas fa-box-open me-1"></i> Recibir OC
			</button>
			<button
				v-if="reg !== ''"
				type="button"
				class="purchase-action action-cancel"
				:disabled="btnAnular || Number(form.compra_estado_id) !== 1"
				@click="anularOrden"
			>
				<i class="fas fa-ban me-1"></i> Anular
			</button>
			<button
				type="button"
				class="purchase-action action-save"
				:disabled="btnGuardar || (reg !== '' && Number(form.compra_estado_id) !== 1)"
				@click="guardarOrden"
			>
				<i class="fas fa-save me-1"></i> Guardar
			</button>
		</div>
	</div>

	<div class="row g-3 align-items-start">
		<div class="col-xl-9">
	<div class="card shadow-sm mb-3">
		<div class="card-header section-header">
			<h6 class="mb-0 fw-bold">
				<span class="section-icon">
					<i class="fas fa-file-invoice"></i>
				</span>
				Datos de la compra
			</h6>
		</div>
		<div class="card-body">
			<div class="row g-3">
				<div class="col-md-6 col-xl-6">
					<label class="form-label fw-bold">
						Proveedor: <span class="text-danger">*</span>
					</label>
					<select class="form-select" v-model="form.proveedor_id" required>
						<option :value="null">Seleccione un proveedor...</option>
						<option v-for="proveedor in cat.proveedores" :key="proveedor.id" :value="proveedor.id">
							{{ proveedor.nombre }}
						</option>
					</select>
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">
						Forma de pago: <span class="text-danger">*</span>
					</label>
					<select class="form-select" v-model="form.forma_pago_id" required>
						<option :value="null">Seleccione...</option>
						<option v-for="forma in cat.formas_pago" :key="forma.id" :value="forma.id">
							{{ forma.nombre }}
						</option>
					</select>
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">
						Moneda: <span class="text-danger">*</span>
					</label>
					<select class="form-select" v-model="form.moneda_id" required>
						<option :value="null">Seleccione...</option>
						<option v-for="moneda in cat.monedas" :key="moneda.id" :value="moneda.id">
							{{ moneda.nombre }}
						</option>
					</select>
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">Número de factura:</label>
					<input type="text" class="form-control" placeholder="Ej. A-00125" v-model="form.factura_numero" />
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">Fecha de factura:</label>
					<input type="date" class="form-control" v-model="form.factura_fecha" />
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">
						Sucursal: <span class="text-danger">*</span>
					</label>
					<select class="form-select" v-model="form.sucursal_id" required>
						<option :value="null">Seleccione...</option>
						<option v-for="sucursal in cat.sucursales" :key="sucursal.id" :value="sucursal.id">
							{{ sucursal.nombre }}
						</option>
					</select>
				</div>
				<div class="col-md-6 col-xl-3">
					<label class="form-label fw-bold">Estado:</label>
					<div>
						<span
							:class="[
								'badge fs-6 px-3 py-2',
								`bg-${form.etiqueta || 'primary'} bg-opacity-15`,
								`text-${form.etiqueta || 'primary'}`,
								`border border-${form.etiqueta || 'primary'}`
							]"
						>
							<i class="fas fa-info-circle me-1"></i> {{ form.nombre_estado || "Creada" }}
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card shadow-sm mb-3">
		<div class="card-header section-header d-flex align-items-center">
			<h6 class="mb-0 fw-bold">
				<span class="section-icon">
					<i class="fas fa-boxes"></i>
				</span>
				Detalle de artículos
			</h6>
			<span class="badge bg-success bg-opacity-15 text-success border border-success ms-auto px-2 py-1">
				{{ detalleActivo.length }} productos
			</span>
		</div>
		<div class="card-body">
			<div class="row g-2 align-items-end mb-3">
				<div class="col-sm-3 col-lg-2">
					<label class="form-label fw-bold">Cantidad</label>
					<input type="number" class="form-control text-center" min="1" v-model.number="cantidadProducto" :disabled="!puedeEditar" />
				</div>
				<div class="col-sm-9 col-lg-6">
					<label class="form-label fw-bold">
						Producto: <span class="text-danger">*</span>
					</label>
					<div class="input-group">
						<span class="input-group-text bg-white">
							<i class="fas fa-barcode opacity-50"></i>
						</span>
						<input
							type="text"
							class="form-control"
							placeholder="Escanear o buscar por código o nombre..."
							v-model="busquedaProducto"
							:disabled="!puedeEditar"
						/>
					</div>
				</div>
				<div class="col-sm-12 col-lg-4 d-flex gap-2">
					<button type="button" class="btn btn-outline-lime flex-fill" :disabled="!puedeEditar" @click="agregarProducto">
						<i class="fas fa-plus me-1"></i> Agregar
					</button>
					<button
						type="button"
						class="btn btn-outline-info"
						:disabled="!puedeEditar"
						@click="abrirBusquedaProductos"
					>
						<i class="fas fa-search me-1"></i> Buscar
					</button>
					<button
						type="button"
						class="btn btn-outline-theme"
						:disabled="!puedeEditar"
						@click="abrirCrearProducto"
					>
						<i class="fas fa-box-open me-1"></i> Crear
					</button>
				</div>
			</div>

			<div class="border rounded overflow-hidden">
				<div class="table-responsive">
					<table class="table table-hover text-nowrap align-middle mb-0">
						<thead class="table-dark">
							<tr>
								<th class="text-center">#</th>
								<th>Código</th>
								<th>Descripción</th>
								<th>Presentación</th>
								<th class="text-center">Vencimiento</th>
								<th class="text-center">Cantidad</th>
								<th class="text-end">Costo</th>
								<th class="text-end">Total</th>
								<th class="text-center"></th>
							</tr>
						</thead>
						<tbody>
							<tr v-if="cargandoDetalle">
								<td colspan="9" class="text-center py-5">
									<div class="spinner-border text-theme mb-2"></div>
									<div>Cargando detalle...</div>
								</td>
							</tr>
							<tr v-else-if="detalleActivo.length === 0">
								<td colspan="9" class="text-center py-5 text-body-secondary">
									<i class="fas fa-box-open fa-2x mb-2 opacity-25"></i>
									<div>Aún no se han agregado productos.</div>
									<small>Utilice el buscador para agregar artículos a la compra.</small>
								</td>
							</tr>
							<tr v-else v-for="(linea, idx) in detalleActivo" :key="linea.id || `${linea.producto_id}-${idx}`">
								<td class="text-center fw-bold">{{ idx + 1 }}</td>
								<td class="fw-bold">{{ linea.codigo }}</td>
								<td>{{ linea.nombre_producto }}</td>
								<td>{{ linea.nombre_um || "-" }}</td>
								<td width="155">
									<input
										v-if="Number(linea.control_vence) === 1"
										type="date"
										class="form-control form-control-sm"
										v-model="linea.fecha_vence"
										:disabled="!puedeEditar"
									/>
									<span v-else class="d-block text-center text-body-secondary">-</span>
								</td>
								<td width="120">
									<input type="number" class="form-control form-control-sm text-center" min="1" v-model.number="linea.cantidad" :disabled="!puedeEditar" />
								</td>
								<td width="140">
									<div class="input-group input-group-sm">
										<span v-if="simboloMoneda" class="input-group-text">{{ simboloMoneda }}</span>
										<input type="number" class="form-control text-end" min="0" step="0.01" v-model.number="linea.precio_costo" :disabled="!puedeEditar" />
									</div>
								</td>
								<td class="text-end fw-bold">{{ formatoMonto(totalLinea(linea)) }}</td>
								<td class="text-center">
									<button type="button" class="btn btn-sm btn-outline-danger" :disabled="!puedeEditar" @click="quitarProducto(linea)">
										<i class="fas fa-trash"></i>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
		</div>

		<div class="col-xl-3">
			<div class="purchase-sidebar">
			<div class="card shadow-sm mb-3">
				<div class="card-header section-header">
					<h6 class="mb-0 fw-bold">
						<span class="section-icon">
							<i class="fas fa-receipt"></i>
						</span>
						Resumen
					</h6>
				</div>
				<div class="card-body">
					<div class="d-flex justify-content-between mb-2">
						<span class="text-body-secondary">Artículos</span>
						<span class="fw-bold">{{ detalleActivo.length }}</span>
					</div>
					<hr />
					<div class="d-flex justify-content-between align-items-center">
						<span class="fs-5 fw-bold">Total de compra</span>
						<span class="fs-3 fw-bold text-theme">{{ formatoMonto(totalCompra) }}</span>
					</div>
				</div>
			</div>

			<div class="card shadow-sm">
				<div class="card-header section-header">
					<h6 class="mb-0 fw-bold">
						<span class="section-icon">
							<i class="fas fa-align-left"></i>
						</span>
						Referencias
					</h6>
				</div>
				<div class="card-body">
					<textarea
						class="form-control"
						rows="5"
						placeholder="Observaciones o referencias de la compra..."
						v-model="form.referencias"
					></textarea>
				</div>
			</div>
			</div>
		</div>
	</div>

	<div class="text-end mt-3">
		<button type="button" class="btn btn-secondary me-2" @click="$emit('cerrar')">
			<i class="fas fa-times me-1"></i> Cancelar
		</button>
		<button
			type="button"
			class="btn btn-theme"
			:disabled="btnGuardar || (reg !== '' && Number(form.compra_estado_id) !== 1)"
			@click="guardarOrden"
		>
			<i class="fas fa-save me-1"></i> Guardar compra
		</button>
	</div>

	<div
		class="modal fade"
		id="mdlBuscarProductoCompra"
		tabindex="-1"
		data-bs-backdrop="static"
		data-bs-keyboard="false"
		aria-hidden="true"
	>
		<div class="modal-dialog modal-xl modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-search me-2"></i>Buscar productos
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<div class="row g-2 mb-3">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text bg-white border-end-0">
									<i class="fas fa-search opacity-50"></i>
								</span>
								<input
									type="text"
									class="form-control border-start-0 ps-0"
									placeholder="Código, nombre, categoría o marca..."
									v-model="filtroProducto.termino"
								/>
							</div>
						</div>
						<div class="col-sm-6 col-lg-3">
							<select class="form-select" v-model="filtroProducto.categoria_id">
								<option :value="null">Todas las categorías</option>
								<option v-for="categoria in cat.categorias" :key="categoria.id" :value="categoria.id">
									{{ categoria.nombre }}
								</option>
							</select>
						</div>
						<div class="col-sm-6 col-lg-3">
							<select class="form-select" v-model="filtroProducto.marca_id">
								<option :value="null">Todas las marcas</option>
								<option v-for="marca in cat.marcas" :key="marca.id" :value="marca.id">
									{{ marca.nombre }}
								</option>
							</select>
						</div>
					</div>

					<div class="border rounded overflow-hidden">
						<div class="table-responsive">
							<table class="table table-sm table-hover text-nowrap align-middle mb-0">
								<thead class="table-primary">
									<tr>
										<th>Código</th>
										<th>Producto</th>
										<th class="text-center">UM</th>
										<th>Categoría</th>
										<th>Marca</th>
										<th class="text-end">Costo</th>
										<th class="text-center" width="70"></th>
									</tr>
								</thead>
								<tbody>
									<tr v-if="cargandoProductos">
										<td colspan="7" class="text-center py-5">
											<div class="spinner-border text-theme mb-2"></div>
											<div>Cargando productos...</div>
										</td>
									</tr>
									<tr v-else-if="productosFiltrados.length === 0">
										<td colspan="7" class="text-center py-5 text-body-secondary">
											<i class="fas fa-box-open fa-2x mb-2 opacity-25"></i>
											<div>No se encontraron productos.</div>
										</td>
									</tr>
									<tr v-else v-for="producto in productosFiltrados" :key="producto.id">
										<td class="fw-bold">{{ producto.codigo }}</td>
										<td>{{ producto.nombre }}</td>
										<td class="text-center">{{ producto.nombre_um }}</td>
										<td>
											<span :class="`badge bg-${producto.etiqueta} bg-opacity-15 text-${producto.etiqueta}`">
												{{ producto.nombre_categoria }}
											</span>
										</td>
										<td>{{ producto.nombre_marca }}</td>
										<td class="text-end">{{ formatoMonto(producto.costo) }}</td>
										<td class="text-center">
											<button type="button" class="btn btn-sm btn-lime" @click="seleccionarProducto(producto)">
												<i class="fas fa-plus"></i>
											</button>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div
		class="modal fade"
		id="mdlCrearProductoCompra"
		tabindex="-1"
		data-bs-backdrop="static"
		data-bs-keyboard="false"
		aria-hidden="true"
	>
		<div class="modal-dialog modal-xl modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-boxes-stacked me-2"></i>Crear producto
					</h5>
					<button
						type="button"
						class="btn-close"
						aria-label="Cerrar"
						@click="cerrarCrearProducto"
					></button>
				</div>
				<div class="modal-body">
					<FormProducto
						v-if="verFormProducto"
						:producto="null"
						@cerrar="cerrarCrearProducto"
						@actualizar="agregarProductoCatalogo"
					/>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import Helper from "@/mixins/Helper.js"
	import Logy from "@/mixins/Logy.js"
	import FormProducto from "@/views/mnt/producto/Form.vue"

	export default {
		name: "CompraForm",
		mixins: [Logy, Helper],
		emits: ["cerrar", "actualizar"],
		props: {
			compra: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			btnAnular: false,
			btnRecibir: false,
			busquedaProducto: null,
			productoSeleccionado: null,
			cantidadProducto: 1,
			verFormProducto: false,
			cargandoDetalle: false,
			cat: {
				marcas: [],
				categorias: [],
				productos: [],
				proveedores: [],
				formas_pago: [],
				monedas: [],
				sucursales: []
			},
			cargandoProductos: false,
			filtroProducto: {
				termino: null,
				categoria_id: null,
				marca_id: null
			}
		}),
		created() {
			this._emit = true
			this.autoBuscar = false
			this.url = "compra/orden"

			if (this.compra) {
				this.setDataForm(this.compra)
				this.form.detalle = []
			} else {
				this.fbase = {
					proveedor_id: null,
					forma_pago_id: null,
					moneda_id: null,
					sucursal_id: null,
					factura_numero: null,
					factura_fecha: null,
					referencias: null,
					detalle: []
				}
			}

			this.getDatos()

			if (this.reg !== "") {
				this.getDetalle()
			}
		},
		methods: {
			urlImpresion(id) {
				const api = this.$http.defaults.baseURL.replace(/\/$/, "")
				return `${api}/${this.$baseUrl}/compra/orden/imprimir/${id}`
			},
			recibirOrden() {
				if (this.reg === "" || Number(this.form.compra_estado_id) !== 1 || Number(this.form.anulado) === 1) {
					this.$toast.error("Solo puede recibir órdenes de compra creadas y no anuladas.")
					return
				}

				if (!confirm("¿Está seguro de recibir esta orden de compra?")) {
					return
				}

				this.btnRecibir = true

				this.$http
				.post(`${this.$baseUrl}/compra/orden/recibir/${this.reg}`)
				.then(res => {
					if (res.data.exito) {
						this.$toast.success(res.data.mensaje)
						this.$emit("actualizar", res.data.linea)
					} else {
						this.$toast.error(res.data.mensaje)
					}
				})
				.catch(e => {
					console.log(e)
					this.$toast.error("No fue posible recibir la orden de compra.")
				})
				.finally(() => {
					this.btnRecibir = false
				})
			},
			anularOrden() {
				if (this.reg === "" || Number(this.form.compra_estado_id) !== 1) {
					this.$toast.error("Solo puede anular órdenes de compra en estado Creada.")
					return
				}

				if (!confirm("¿Está seguro de anular esta orden de compra?")) {
					return
				}

				this.btnAnular = true

				this.$http
				.post(`${this.$baseUrl}/compra/orden/anular/${this.reg}`)
				.then(res => {
					if (res.data.exito) {
						this.$toast.success(res.data.mensaje)
						this.$emit("actualizar", res.data.linea)
					} else {
						this.$toast.error(res.data.mensaje)
					}
				})
				.catch(e => {
					console.log(e)
					this.$toast.error("No fue posible anular la orden de compra.")
				})
				.finally(() => {
					this.btnAnular = false
				})
			},
			guardarOrden() {
				if (!this.form.proveedor_id ||
					!this.form.forma_pago_id ||
					!this.form.moneda_id ||
					!this.form.sucursal_id) {
					this.$toast.error("Complete los campos marcados con *.")
					return
				}

				if (this.detalleActivo.length === 0) {
					this.$toast.error("Agregue al menos un producto a la orden de compra.")
					return
				}

				const detalleInvalido = this.detalleActivo.some(linea =>
					!linea.producto_id ||
					!linea.unidad_medida_id ||
					!Number.isFinite(Number(linea.cantidad)) ||
					Number(linea.cantidad) <= 0 ||
					linea.precio_costo === null ||
					linea.precio_costo === "" ||
					!Number.isFinite(Number(linea.precio_costo)) ||
					Number(linea.precio_costo) < 0
				)

				if (detalleInvalido) {
					this.$toast.error("Revise la cantidad y el costo de los productos.")
					return
				}

				Logy.methods.guardar.call(this)
			},
			abrirBusquedaProductos() {
				this.$abrirModal("mdlBuscarProductoCompra")
			},
			abrirCrearProducto() {
				this.verFormProducto = true
				this.$abrirModal("mdlCrearProductoCompra")
			},
			cerrarCrearProducto() {
				this.verFormProducto = false
				this.$cerrarModal("mdlCrearProductoCompra")
			},
			agregarProductoCatalogo(producto) {
				this.cat.productos.push(producto)
				this.productoSeleccionado = producto

				if (this.agregarProducto()) {
					this.cerrarCrearProducto()
				}
			},
			getDatos() {
				this.cargandoProductos = true

				this.$http
				.get(`${this.$baseUrl}/compra/orden/get_datos`)
				.then(res => {
					this.cat = res.data.cat || {
						marcas: [],
						categorias: [],
						productos: [],
						proveedores: [],
						formas_pago: [],
						monedas: [],
						sucursales: []
					}
				})
				.catch(e => {
					console.log(e)
					this.cat = {
						marcas: [],
						categorias: [],
						productos: [],
						proveedores: [],
						formas_pago: [],
						monedas: [],
						sucursales: []
					}
				})
				.finally(() => {
					this.cargandoProductos = false
				})
			},
			getDetalle() {
				this.cargandoDetalle = true

				this.$http
				.get(`${this.$baseUrl}/compra/detalle/buscar`, {
					params: { compra_id: this.reg }
				})
				.then(res => {
					this.form.detalle = (res.data.lista || []).map(linea => ({
						...linea,
						fecha_vence: linea.fecha_vence ? linea.fecha_vence.substring(0, 10) : null
					}))
				})
				.catch(e => {
					console.log(e)
					this.form.detalle = []
				})
				.finally(() => {
					this.cargandoDetalle = false
				})
			},
			seleccionarProducto(producto) {
				this.productoSeleccionado = producto
				this.agregarProducto()
			},
			agregarProducto() {
				let producto = this.productoSeleccionado

				if (!producto && this.busquedaProducto) {
					const termino = this.busquedaProducto.toLowerCase().trim()

					producto = this.cat.productos.find(item =>
						String(item.codigo || "").toLowerCase() === termino ||
						String(item.codigo_barra || "").toLowerCase() === termino
					)
				}

				if (!producto) {
					this.$toast.error("Seleccione un producto.")
					return false
				}

				if (!this.cantidadProducto || this.cantidadProducto <= 0) {
					this.$toast.error("Ingrese una cantidad válida.")
					return false
				}

				const existente = this.form.detalle.find(linea =>
					Number(linea.anulado || 0) === 0 &&
					String(linea.producto_id) === String(producto.id)
				)

				if (existente) {
					existente.cantidad = Number(existente.cantidad) + Number(this.cantidadProducto)
				} else {
					this.form.detalle.push({
						producto_id: producto.id,
						unidad_medida_id: producto.unidad_medida_id,
						producto_presentacion_id: null,
						codigo: producto.codigo,
						nombre_producto: producto.nombre,
						nombre_um: producto.nombre_um,
						control_vence: Number(producto.control_vence || 0),
						fecha_vence: null,
						cantidad: Number(this.cantidadProducto),
						precio_costo: Number(producto.costo || 0),
						total_costo: 0,
						anulado: 0
					})
				}

				this.productoSeleccionado = null
				this.busquedaProducto = null
				this.cantidadProducto = 1

				return true
			},
			quitarProducto(linea) {
				if (linea.id) {
					linea.anulado = 1
				} else {
					const idx = this.form.detalle.indexOf(linea)

					if (idx >= 0) {
						this.form.detalle.splice(idx, 1)
					}
				}
			},
			totalLinea(linea) {
				return Number(linea.cantidad || 0) * Number(linea.precio_costo || 0)
			},
			formatoMonto(valor) {
				const monto = this.formatoNumero(valor, "0,0.00")

				return this.simboloMoneda ? `${this.simboloMoneda} ${monto}` : monto
			}
		},
		computed: {
			puedeEditar() {
				return this.reg === "" || (this.form.compra_estado_id == 1 && this.form.anulado != 1)
			},
			simboloMoneda() {
				const moneda = this.cat.monedas.find(item =>
					String(item.id) === String(this.form.moneda_id)
				)

				return moneda?.simbolo || ""
			},
			detalleActivo() {
				return (this.form.detalle || []).filter(linea => Number(linea.anulado || 0) === 0)
			},
			totalCompra() {
				return this.detalleActivo.reduce((total, linea) => total + this.totalLinea(linea), 0)
			},
			productosFiltrados() {
				const termino = (this.filtroProducto.termino || "").toLowerCase().trim()

				return this.cat.productos.filter(producto => {
					if (Number(producto.activo) !== 1) return false
					if (this.filtroProducto.categoria_id &&
						String(producto.categoria_id) !== String(this.filtroProducto.categoria_id)) return false
					if (this.filtroProducto.marca_id &&
						String(producto.marca_id) !== String(this.filtroProducto.marca_id)) return false

					if (!termino) return true

					return [
						producto.codigo,
						producto.codigo_barra,
						producto.nombre,
						producto.nombre_categoria,
						producto.nombre_marca
					].some(valor => String(valor || "").toLowerCase().includes(termino))
				})
			}
		},
		components: {
			FormProducto
		}
	}
</script>

<style scoped>
	.purchase-actions {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 1.5rem;
	}

	.purchase-action {
		display: inline-flex;
		align-items: center;
		padding: 0.2rem 0;
		border: 0;
		background: transparent;
		color: var(--bs-body-color);
		font-weight: 500;
		text-decoration: none;
		transition: color 0.15s ease;
	}

	.purchase-action i {
		width: 1.1rem;
		color: var(--bs-secondary-color);
		text-align: center;
	}

	.purchase-action:hover {
		color: var(--bs-theme);
	}

	.purchase-action:hover i,
	.action-save i {
		color: var(--bs-theme);
	}

	.action-receive i {
		color: var(--bs-success);
	}

	.action-cancel i {
		color: var(--bs-danger);
	}

	.purchase-action:disabled {
		opacity: 0.45;
		pointer-events: none;
	}

	.section-header {
		padding: 0.65rem 1rem;
		background-color: rgba(var(--bs-tertiary-bg-rgb), 0.55);
		border-bottom: 1px solid var(--bs-border-color);
	}

	.section-icon {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 1.75rem;
		height: 1.75rem;
		margin-right: 0.5rem;
		color: var(--bs-theme);
		background-color: rgba(var(--bs-theme-rgb), 0.12);
		border-radius: 0.4rem;
	}

	@media (min-width: 1200px) {
		.purchase-sidebar {
			position: sticky;
			top: calc(var(--bs-app-header-height, 3.75rem) + 1rem);
		}
	}
</style>
