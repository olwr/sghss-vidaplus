import { describe, expect, it } from 'vitest'
import { cpfValido, formatarCpf } from '@/lib/cpf'

describe('cpf', () => {
  it('aceita CPF válido com ou sem máscara', () => {
    expect(cpfValido('529.982.247-25')).toBe(true)
    expect(cpfValido('52998224725')).toBe(true)
  })
  it('rejeita dígito errado, repetidos e tamanho incorreto', () => {
    expect(cpfValido('529.982.247-24')).toBe(false)
    expect(cpfValido('111.111.111-11')).toBe(false)
    expect(cpfValido('123')).toBe(false)
  })
  it('formata progressivamente', () => {
    expect(formatarCpf('52998224725')).toBe('529.982.247-25')
    expect(formatarCpf('5299')).toBe('529.9')
  })
})
