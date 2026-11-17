import { describe, expect, it } from 'vitest'
import { z } from 'zod'
import { toTypedSchema } from '@/lib/schemaZod'

const schema = toTypedSchema(
  z.object({
    email: z.string().email('E-mail inválido.'),
    itens: z.array(z.object({ q: z.number().min(1, 'Mín. 1') })),
  }),
)

describe('adaptador zod 4 → vee-validate (A30)', () => {
  it('valida e devolve o valor tipado', async () => {
    const r = await schema.parse({ email: 'a@b.com', itens: [{ q: 2 }] })
    expect(r.errors).toEqual([])
    expect(r.value).toEqual({ email: 'a@b.com', itens: [{ q: 2 }] })
  })
  it('mapeia caminhos aninhados como itens[0].q', async () => {
    const r = await schema.parse({ email: 'x', itens: [{ q: 0 }] })
    const caminhos = r.errors.map((e) => e.path)
    expect(caminhos).toContain('email')
    expect(caminhos).toContain('itens[0].q')
    expect(r.errors.find((e) => e.path === 'email')?.errors).toEqual(['E-mail inválido.'])
  })
})
