<template>
	<form @submit.prevent="guardar">
		<div class="alert alert-light fw-bold">
			Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
		</div>
		
		<div class="row g-2">
			<div class="col-sm-6">
				<label 
					for="inputNombre" 
					class="form-label fw-bold"
				>
					Nombre: <span class="fw-bold text-danger">*</span>
				</label>
				<input 
					id="inputNombre" 
					type="text" 
					class="form-control"
					v-model="form.nombre"
					required
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="selectRol" 
					class="form-label fw-bold"
				>
					Rol: <span class="text-danger">*</span>
				</label>
				<select 
					name="selectRol" 
					id="selectRol" 
					class="form-select"
					v-model="form.rol_id"
				>
					<option :value="null">Seleccione un rol...</option>
					<option v-for="i in cat.roles" :value="i.id">{{ i.nombre }}</option>
				</select>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputAlias" 
					class="form-label fw-bold"
				>
					Usuario: <span class="fw-bold text-danger">*</span>
				</label>
				<input 
					id="inputAlias" 
					type="text" 
					class="form-control" 
					v-model="form.alias"
					required
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputClave" 
					class="form-label fw-bold"
				>
					Clave: <span class="fw-bold text-danger">*</span>
				</label>
				<input 
					id="inputClave" 
					type="password" 
					class="form-control" 
					v-model="form.clave"
					:required="usuario == null"
					:disabled="reg != ''"
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputCorreo" 
					class="form-label fw-bold"
				>
					Correo:
				</label>
				<input 
					id="inputCorreo" 
					type="text" 
					class="form-control"
					v-model="form.correo"
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputTelefono" 
					class="form-label fw-bold"
				>
					Teléfono:
				</label>
				<input 
					id="inputTelefono" 
					type="text" 
					class="form-control"
					v-model="form.telefono"
				/>
			</div>

			<div class="col-sm-6" v-if="reg != ''">
				<div class="form-check form-switch">
					<input 
						class="form-check-input" 
						type="checkbox" 
						role="switch" 
						id="chkActivo" 
						:true-value="1"
						:false-value="0"
						v-model="form.activo"
					>
					<label class="form-check-label" for="chkActivo">Activo</label>
				</div>
			</div>	

			<div class="col-sm-12 text-end mt-4">
				<button
					type="button" 
					class="btn btn-secondary me-2" 
					@click="$emit('cerrar')"
					:disabled="btnGuardar"
				>
					<i class="fas fa-times me-1"></i>Cancelar
				</button>
				<button 
					type="submit" 
					class="btn btn-theme"
					:disabled="btnGuardar"
				>
					<span 
						v-if="btnGuardar"
						class="spinner-border spinner-border-sm" 
						aria-hidden="true"
					></span>
					<i v-else class="fas fa-save me-1"></i>
					
					{{ !btnGuardar ? 'Guardar' : 'Guardando...'}}
				</button>
			</div>
		</div>
	</form>
</template>

<script>
	import Logy from "@/mixins/Logy.js"

	export default {
		name: "FormUsuario",
		mixins: [Logy],
		props: {
			usuario: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			form: {},
			cat: {}
		}),
		created() {
			this.url = "usuario"
			this._emit = true
			this.autoBuscar = false
			this.getDatos()

			if (this.usuario != null) {
				this.setDataForm(this.usuario)
			} else {
				this.fbase = {
					rol_id: null,
					activo: 1
				}
			}
		},
		methods: {
			getDatos() {
				this.inicio = true

				this.$http
				.get(`${this.$baseUrl}/usuario/get_datos`)
				.then(res => {
					this.inicio = false
					this.cat = res.data.cat
				}).catch(e => {
					this.btnBuscar = false
					this.inicio    = false
					console.log(e)
				});
			}
		}
	}
</script>