<template>
	<div class="d-flex align-items-center mb-3">
		<div>
			<ul class="breadcrumb">
				<li class="breadcrumb-item">
					<a href="#">MANTENIMIENTO</a>
				</li>
				<li class="breadcrumb-item active">
					USUARIOS
				</li>
			</ul>

			<h1 class="page-header mb-0">
				Usuarios
			</h1>
		</div>

		<div class="ms-auto">
			<a href="#" class="btn btn-theme" @click="frmUsuario(null)">
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
							<th>Nombre</th>
							<th>Usuario</th>
							<th>Rol</th>
							<th>Correo</th>
							<th>Telefono</th>
							<th>Empresa</th>
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
							<td class="fw-bold">
								<a href="javascript:;" class="text-decoration-none" @click="frmUsuario(i)">{{ i.nombre }}</a>
							</td>
							<td>{{ i.alias }}</td>
							<td>{{ i.nombre_rol }}</td>
							<td>{{ i.correo }}</td>
							<td>{{ i.telefono }}</td>
							<td>{{ i.nombre_empresa }}</td>
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
		id="mdlUsuario" 
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
						<i class="fas fa-user me-1"></i> Usuario
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
						:usuario="usuario"
						@cerrar="cerrarFrm"
						@actualizar="actualizarLista"
					/>
				</div>
			</div>
		</div>
	</div>

</template>

<script>
	import Form from "@/views/usuario/Form.vue"

	export default {
		name: "Usuario",
		data: () => ({
			cargando: false,
			verForm: false,
			actual: 1,
			bform: {},
			usuario: null,
			lista: []
		}),
		created() {
			this.buscar()
		},
		methods: {
			buscar() {
				this.cargando = true

				this.$http
				.get(`${this.$baseUrl}/usuario/buscar`, {params: this.bform})
				.then(res => {
					this.cargando = false
					this.lista = res.data.lista
				}).catch(e => {
					alert(e)
					this.cargando = false
				})
			},
			frmUsuario(obj) {
				this.verForm = true
				this.usuario = obj
				this.$abrirModal("mdlUsuario")
			},
			cerrarFrm() {
				this.verForm = false
				this.usuario = null
				this.$cerrarModal("mdlUsuario")
			},
			actualizarLista(obj) {
				if (this.usuario === null) {
					this.lista.unshift(obj)
				} else {
					for (let i in this.usuario) {
						this.usuario[i] = obj[i]
					}
				}

				this.cerrarFrm()
			}
		},
		components: {
			Form
		}
	}
</script>