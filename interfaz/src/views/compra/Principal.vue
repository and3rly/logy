<template>
	<template v-if="!verForm">
	<div class="d-flex align-items-center mb-3">
		<div>
			<ul class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">COMPRAS</a></li>
				<li class="breadcrumb-item active">ÓRDENES DE COMPRA</li>
			</ul>
			<h1 class="page-header mb-0">Compras</h1>
		</div>

		<div class="ms-auto">
			<button type="button" class="btn btn-theme" @click="frmOrden(null)">
				<i class="fa fa-plus-circle fa-fw me-1"></i>
				Nueva compra
			</button>
		</div>
	</div>

	<div class="card shadow-sm mb-3">
		<div class="card-body">
			<div class="row g-2 align-items-end">
				<div class="col-lg-5">
					<label class="form-label fw-bold">Buscar</label>
					<div class="input-group">
						<span class="input-group-text bg-white border-end-0">
							<i class="fa fa-search opacity-5"></i>
						</span>
						<input
							type="text"
							class="form-control border-start-0 ps-0"
							placeholder="Número de compra, factura o proveedor..."
							v-model="bform.termino"
							@keyup.enter="buscar"
						/>
					</div>
				</div>
				<div class="col-sm-4 col-lg-2">
					<label class="form-label fw-bold">Estado</label>
					<select class="form-select" v-model="bform.compra_estado_id">
						<option :value="null">Todos</option>
						<option value="1">Creada</option>
						<option value="2">Recibida</option>
						<option value="3">Anulada</option>
					</select>
				</div>
				<div class="col-sm-4 col-lg-2">
					<label class="form-label fw-bold">Desde</label>
					<input type="date" class="form-control" v-model="bform.fecha_desde" />
				</div>
				<div class="col-sm-4 col-lg-2">
					<label class="form-label fw-bold">Hasta</label>
					<input type="date" class="form-control" v-model="bform.fecha_hasta" />
				</div>
				<div class="col-lg-1 d-grid">
					<button type="button" class="btn btn-outline-secondary" @click="buscar">
						<i class="fas fa-filter"></i>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card shadow-sm">
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-hover text-nowrap align-middle m-0">
					<thead class="table-primary">
						<tr>
							<th class="text-center">#</th>
							<th>Número</th>
							<th>Fecha</th>
							<th>Proveedor</th>
							<th>Factura</th>
							<th>Forma de pago</th>
							<th class="text-end">Total</th>
							<th class="text-center">Estado</th>
							<th class="text-center">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<tr v-if="cargando">
							<td colspan="9" class="text-center py-5">
								<div class="spinner-border text-theme mb-2"></div>
								<div>Cargando...</div>
							</td>
						</tr>
						<tr v-else-if="lista.length === 0">
							<td colspan="9" class="text-center py-5 text-body-secondary">
								<i class="fas fa-shopping-cart fa-2x mb-2 opacity-25"></i>
								<div>No se encontraron compras.</div>
							</td>
						</tr>
						<tr v-else v-for="(compra, idx) in lista" :key="compra.id">
							<td class="text-center fw-bold">{{ idx + 1 }}</td>
							<td class="fw-bold">{{ compra.numero }}</td>
							<td>{{ formatoFecha(compra.fecha, 2) }}</td>
							<td>{{ compra.nombre_proveedor }}</td>
							<td>{{ compra.factura_numero || "-" }}</td>
							<td>
								<span
									:class="[
										'status-badge',
										compra.forma_pago_id == 1
											? 'status-success'
											: 'status-purple'
									]"
								>
									<span class="status-dot"></span>
									{{ compra.nombre_forma_pago }}
								</span>
							</td>
							<td class="text-end fw-bold">
								{{ compra.simbolo_moneda }} {{ formatoNumero(compra.total_costo, "0,0.00") }}
							</td>
							<td class="text-center">
								<span
									:class="[
										'status-badge',
										`status-${compra.etiqueta || 'secondary'}`
									]"
								>
									<span class="status-dot"></span>
									{{ compra.nombre_estado }}
								</span>
							</td>
							<td class="text-center">
								<button type="button" class="btn btn-sm btn-outline-theme" @click="frmOrden(compra)">
									<i class="fas fa-edit"></i>
								</button>
								<a
									:href="urlImpresion(compra.id)"
									target="_blank"
									rel="noopener"
									class="btn btn-sm btn-outline-dark ms-1"
								>
									<i class="fas fa-print"></i>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	</template>

	<Form
		v-else
		:compra="compra"
		@cerrar="cerrarFrm"
		@actualizar="actualizarLista"
	/>
</template>

<script>
	import Helper from "@/mixins/Helper.js"
	import Form from "@/views/compra/Form.vue"

	export default {
		name: "CompraPrincipal",
		mixins: [Helper],
		data: () => ({
			cargando: false,
			verForm: false,
			compra: null,
			lista: [],
			bform: {
				termino: null,
				compra_estado_id: null,
				fecha_desde: null,
				fecha_hasta: null
			}
		}),
		created() {
			const hoy = new Date()
			const anio = hoy.getFullYear()
			const mes = String(hoy.getMonth() + 1).padStart(2, "0")
			const dia = String(hoy.getDate()).padStart(2, "0")

			this.bform.fecha_desde = `${anio}-${mes}-01`
			this.bform.fecha_hasta = `${anio}-${mes}-${dia}`
			this.buscar()
		},
		methods: {
			urlImpresion(id) {
				const api = this.$http.defaults.baseURL.replace(/\/$/, "")
				return `${api}/${this.$baseUrl}/compra/orden/imprimir/${id}`
			},
			frmOrden(obj) {
				this.compra = obj
				this.verForm = true
			},
			cerrarFrm() {
				this.compra = null
				this.verForm = false
			},
			actualizarLista(obj) {
				if (this.compra === null) {
					this.lista.unshift(obj)
				} else {
					for (let i in this.compra) {
						this.compra[i] = obj[i]
					}
				}

				this.cerrarFrm()
			},
			buscar() {
				this.cargando = true

				this.$http
				.get(`${this.$baseUrl}/compra/orden/buscar`, { params: this.bform })
				.then(res => {
					this.lista = res.data.lista || []
				})
				.catch(e => {
					console.log(e)
					this.lista = []
				})
				.finally(() => {
					this.cargando = false
				})
			}
		},
		components: {
			Form
		}
	}
</script>

<style scoped>
	.status-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.3rem 0.55rem;
		border-radius: 0.4rem;
		font-size: 0.75rem;
		font-weight: 500;
		line-height: 1;
		white-space: nowrap;
	}

	.status-dot {
		width: 0.5rem;
		height: 0.5rem;
		border-radius: 50%;
		background-color: currentColor;
	}

	.status-success,
	.status-lime {
		color: #12876f;
		background-color: #cef3e9;
	}

	.status-purple {
		color: #7253b5;
		background-color: #e9e1f8;
	}

	.status-primary {
		color: #3268cf;
		background-color: #d9e5ff;
	}

	.status-danger {
		color: #d83b3b;
		background-color: #f8d3d3;
	}

	.status-secondary {
		color: #64748b;
		background-color: #e2e8f0;
	}
</style>
