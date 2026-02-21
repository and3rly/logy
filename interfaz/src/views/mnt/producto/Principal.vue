<template>
	<div class="d-flex align-items-center mb-3">
		<div>
			<ul class="breadcrumb">
				<li class="breadcrumb-item">
					<a href="#">MANTENIMIENTO</a>
				</li>
				<li class="breadcrumb-item active">
					PRODUCTOS
				</li>
			</ul>

			<h1 class="page-header mb-0">
				Productos
			</h1>
		</div>

		<div class="ms-auto">
			<a href="#" class="btn btn-theme" @click="frmProducto(null)">
				<i class="fa fa-plus-circle fa-fw me-1"></i>
				Nuevo
			</a>
		</div>
	</div>

	<div class="card shadow-sm"> 
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-hover text-nowrap m-0">
					<thead class="table-primary">
						<tr>
							<th class="text-center">#</th>
							<th class="text-center" width="100">Imagen</th>
							<th>Código</th>
							<th>Producto</th>
							<th class="text-center">Tipo</th>
							<th class="text-center">Categoría</th>
							<th>Marca</th>
							<th>Um</th>
							<th class="text-center">Estado</th>
						</tr>
					</thead>
					<tbody>
						<tr v-if="cargando">
							<td colspan="100" class="text-center py-2">
								<div class="spinner-border text-dark"></div><br>
								Cargando...
							</td> 
						</tr>
						<tr v-if="lista.length == 0 && !cargando">
							<td
								class="text-center py-2"
								colspan="100"
							>
								No se encontraron registros.
							</td>
						</tr>
						<tr 
							v-else 
							v-for="(i, idx) in lista" 
							style="cursor: pointer;" 
							:key="idx"
						>
							<td class="text-center fw-bold">{{ idx + 1 }}</td>
							<td>
								<Imagen
									:img="i.foto"
									:width="50"
								></Imagen>
							</td>
							<td>
								{{i.codigo}}
							</td>
							<td class="fw-bold">
								<a href="javascript:;" class="text-decoration-none" @click="frmProducto(i)">{{ i.nombre }}</a>
							</td>
							<td class="text-center">{{ i.tipo_producto }}</td>
							<td class="text-center">
								<span
									:class="'badge bg-'+i.etiqueta+' text-'+i.etiqueta+'-800 bg-opacity-25 px-2 pt-5px pb-5px rounded fs-12px d-inline-flex align-items-center'"
								>
									<i :class="'fa fa-circle text-'+i.etiqueta+' fs-9px fa-fw me-5px'"></i> {{ i.nombre_categoria }}
								</span>
							</td>
							<td>{{ i.nombre_marca }}</td>
							<td>{{ i.nombre_um }}</td>
							<td class="text-center">
								<i v-if="i.activo == 1" class="fas fa-check-circle text-success"></i>
								<i v-else class="fas fa-times-circle text-danger"></i>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div 
		class="modal fade" 
		id="mdlProducto" 
		data-bs-backdrop="static" 
		data-bs-keyboard="false" 
		tabindex="-1" 
		aria-labelledby="staticBackdropLabel" 
		aria-hidden="true"
	>
		<div class="modal-dialog modal-xl modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-boxes-stacked me-1"></i> Producto
					</h5>
					<button 
						type="button" 
						class="btn-close" 
						data-bs-dismiss="modal" 
						aria-label="Close"
						@click="cerrarFrm"
					></button>
				</div>
				<div class="modal-body">
					<Form
						v-if="verForm"
						:producto="producto"
						@cerrar="cerrarFrm"
						@actualizar="actualizarLista"
					/>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import Imagen from "@/components/general/Imagen.vue"
	import Form from "@/views/mnt/producto/Form.vue"

	export default {
		name: "Producto",
		emits: ["cerrar", "actualizar"],
		data: () => ({
			verForm: false,
			cargando: false,
			lista: [],
			bform: {}
		}),
		created() {
			this.buscar()
		},
		methods: {
			buscar() {
				this.inicio = true 

				this.$http
				.get(`${this.$baseUrl}/mnt/producto/buscar`, {params: this.bform})
				.then(res => {

					this.inicio = false
					this.lista = res.data.lista

				}).catch(e => {
					this.inicio = false
					console.log(e)
				})
			},
			actualizarLista(obj) {
				if (this.producto === null) {
					this.lista.push(obj)
					this.producto = obj

				} else {
					for (let i in this.producto) {
						this.producto[i] = obj[i]
					}
				}
			},
			frmProducto(obj) {
				this.verForm = true
				this.producto = obj
				this.$abrirModal("mdlProducto")
			},
			cerrarFrm() {
				this.verForm = false
				this.producto = null
				this.$cerrarModal("mdlProducto")
			},
		},
		components: {
			Form,
			Imagen
		}
	}
</script>