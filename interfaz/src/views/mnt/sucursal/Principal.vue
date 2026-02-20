<template>
	<div class="d-flex align-items-center mb-3">
		<div>
			<ul class="breadcrumb">
				<li class="breadcrumb-item">
					<a href="#">MANTENIMIENTO</a>
				</li>
				<li class="breadcrumb-item active">
					SUCURSALES
				</li>
			</ul>

			<h1 class="page-header mb-0">
				Sucursales
			</h1>
		</div>

		<div class="ms-auto">
			<a href="#" class="btn btn-theme" @click="frmSucursal(null)">
				<i class="fa fa-plus-circle fa-fw me-1"></i>
				Nuevo
			</a>
		</div>
	</div>

	<div class="mb-2">
		<div class="input-group mt-3">
			<input 
				type="text" 
				class="form-control ps-35px"
				placeholder="Buscar..." 
				v-model="bform.termino"
				style="border-radius: 4px;" 
			/>
			<div class="input-group-text position-absolute top-0 bottom-0 bg-none border-0" style="z-index: 1020;">
				<i class="fa fa-search opacity-5"></i>
			</div>

			<div 
				v-if="bform.termino"
				class="input-group-text position-absolute top-0 bottom-0 bg-none border-0 end-0"
				style="z-index: 1020; cursor: pointer;"
				@click="bform.termino = null"
			>
				<i class="fa fa-times opacity-5"></i>
			</div>
		</div>
	</div>

	<div class="card shadow-sm"> 
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-hover text-nowrap m-0">
					<thead class="table-primary">
						<tr>
							<th class="text-center">#</th>
							<th>Nombre</th>
							<th>Dirección</th>
							<th>Teléfono</th>
							<th>Correo</th>
							<th>Departamento</th>
							<th>Municipio</th>
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
							v-for="(i, idx) in filtrada" 
							style="cursor: pointer;" 
							:key="idx"
						>
							<td class="text-center fw-bold">{{ idx + 1 }}</td>
							<td class="fw-bold">
								<a href="javascript:;" class="text-decoration-none" @click="frmSucursal(i)">{{ i.nombre }}</a>
							</td>
							<td>{{ i.direccion }}</td>
							<td>{{ i.telefono }}</td>
							<td>{{ i.correo }}</td>
							<td>{{ i.nombre_departamento }}</td>
							<td>{{ i.nombre_municipio }}</td>
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
		id="mdlSucursal" 
		data-bs-backdrop="static" 
		data-bs-keyboard="false" 
		tabindex="-1" 
		aria-labelledby="staticBackdropLabel" 
		aria-hidden="true"
	>
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-home me-1"></i> Sucursal
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
						:sucursal="sucursal"
						@cerrar="cerrarFrm"
						@actualizar="actualizarLista"
					/>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import Form from "@/views/mnt/sucursal/Form.vue"

	export default {
		name: "Sucursal",
		data: () => ({
			cargando: false,
			verForm: false,
			actual: 1,
			bform: {
				termino: null
			},
			sucursal: null,
			lista: []
		}),
		created() {
			this.buscar()
		},
		methods: {
			buscar() {
				this.cargando = true

				this.$http
				.get(`${this.$baseUrl}/mnt/sucursal/buscar`, {params: this.bform})
				.then(res => {
					this.cargando = false
					this.lista = res.data.lista
				}).catch(e => {
					alert(e)
					this.cargando = false
				})
			},
			frmSucursal(obj) {
				this.verForm = true
				this.sucursal = obj
				this.$abrirModal("mdlSucursal")
			},
			cerrarFrm() {
				this.verForm = false
				this.sucursal = null
				this.$cerrarModal("mdlSucursal")
			},
			actualizarLista(obj) {
				if (this.sucursal === null) {
					this.lista.unshift(obj)
				} else {
					for (let i in this.sucursal) {
						this.sucursal[i] = obj[i]
					}
				}

				this.cerrarFrm()
			}
		},
		computed: {
			filtrada() {
				if (!this.bform.termino) return this.lista;
				
				let termino = this.bform.termino.toLowerCase().trim();

				return this.lista.filter(obj =>
					Object.values(obj).some(val =>
						val != null && String(val).toLowerCase().includes(termino)
						)
					);
			}
		},
		components: {
			Form
		}
	}
</script>