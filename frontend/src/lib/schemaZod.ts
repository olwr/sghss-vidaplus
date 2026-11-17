import type { TypedSchema, TypedSchemaError } from 'vee-validate'
import { z } from 'zod'

/**
 * Adaptador mínimo zod 4 → vee-validate 4 (substitui o @vee-validate/zod, que exige zod 3).
 * Só implementa o contrato `TypedSchema` do vee-validate: parse (obrigatório), cast e describe.
 */
export function toTypedSchema<T extends z.ZodType>(
  schema: T,
): TypedSchema<z.input<T>, z.output<T>> {
  return {
    __type: 'VVTypedSchema',
    async parse(values) {
      const resultado = await schema.safeParseAsync(values)
      if (resultado.success) return { value: resultado.data, errors: [] }

      // vee-validate usa "a.b[0].c"; o zod entrega o caminho como array
      const porCaminho = new Map<string, TypedSchemaError>()
      for (const issue of resultado.error.issues) {
        const caminho = issue.path.reduce<string>(
          (acc, parte) =>
            typeof parte === 'number'
              ? `${acc}[${parte}]`
              : acc
                ? `${acc}.${String(parte)}`
                : String(parte),
          '',
        )
        const atual = porCaminho.get(caminho) ?? { path: caminho, errors: [] }
        atual.errors.push(issue.message)
        porCaminho.set(caminho, atual)
      }
      return { errors: [...porCaminho.values()] }
    },
    cast(values) {
      // sem transformação prévia: devolve o que o formulário já tem
      return values as z.input<T>
    },
    describe(path) {
      if (!path) return { required: !schema.isOptional(), exists: true }
      const campo = schema instanceof z.ZodObject ? schema.shape[path as string] : undefined
      return campo
        ? { required: !campo.isOptional(), exists: true }
        : { required: false, exists: false }
    },
  }
}
