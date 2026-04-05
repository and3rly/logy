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
					for="inputIdentificacion" 
					class="form-label fw-bold"
				>
					Identificación:
				</label>
				<input 
					id="inputIdentificacion" 
					type="text" 
					class="form-control"
					v-model="form.identificacion"
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputCorreo" 
					class="form-label fw-bold"
				>
					Dirección:
				</label>
				<input 
					id="inputCorreo" 
					type="text" 
					class="form-control"
					v-model="form.direccion"
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

			<div class="col-sm-6">
				<div class="form-check form-switch mt-4">
					<input
						class="form-check-input"
						type="checkbox"
						role="switch"
						id="chkCredito"
						:true-value="1"
						:false-value="0"
						v-model="form.credito"
					>
					<label class="form-check-label" for="chkCredito">Maneja Crédito</label>
				</div>
			</div>

			<template v-if="form.credito == 1">
				<div class="col-sm-6">
					<label for="inputCreditoLimite" class="form-label fw-bold">
						Límite de Crédito:
					</label>
					<input
						id="inputCreditoLimite"
						type="number"
						class="form-control"
						v-model="form.credito_limite"
						min="0"
						step="0.01"
						placeholder="0.00"
					/>
				</div>
				<div class="col-sm-6">
					<label for="inputCreditoDias" class="form-label fw-bold">
						Días de Crédito:
					</label>
					<input
						id="inputCreditoDias"
						type="number"
						class="form-control"
						v-model="form.credito_dias"
						min="0"
						placeholder="Ej: 30"
					/>
				</div>
			</template>

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
		name: "FormProveedor",
		mixins: [Logy],
		props: {
			proveedor: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			form: {},
			cat: {}
		}),
		created() {
			this._emit = true
			this.autoBuscar = false
			this.url = "mnt/proveedor"

			if (this.proveedor != null) {
				this.setDataForm(this.proveedor)
			} else {
				this.fbase = {
					credito_limite: 0,
					credito_dias: 0,
					credito: 0,
					activo: 1
				}
			}
		}
	}
</script>