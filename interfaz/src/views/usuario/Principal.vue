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
			<a href="#" class="btn btn-theme">
				<i class="fa fa-plus-circle fa-fw me-1"></i>
				Nuevo
			</a>
		</div>
	</div>

	<div class="card">
		<div class="card-body p-0">
			<div class="table-responsive m-0">
				<table class="table table-sm table-hover">
					<thead class="bg-light">
						<th>#</th>
						<th>Nombre</th>
						<th>Alias</th>
						<th>Correo</th>
						<th>Telefono</th>
						<th>Empresa</th>
					</thead>
					<tbody>
						<tr>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	
</template>

<script>
	export default {
		name: "Usuario",
		data: () => ({
			cargando: false,
			bform: {},
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
			}
		}
	}
</script>