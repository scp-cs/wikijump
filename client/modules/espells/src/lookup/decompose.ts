import iterate from "iterare"
import type { Aff, Flags } from "../aff"
import { Affix, Suffix } from "../aff/affix"
import { reverse } from "../util"
import { AffixForm } from "./forms"

/**
 * Yields permutations of a word split up (with whitespace) using the
 * `BREAK` rules given by the spellchecker's {@link Aff} data.
 *
 * @param aff - The affix data to use.
 * @param text - The word/text to split.
 * @param depth - The current depth of the check. Used by this function
 *   when calling itself recursively. There isn't any need to set it yourself.
 */
export function* breakWord(aff: Aff, text: string, depth = 0): Iterable<string[]> {
  if (depth > 10) return
  yield [text]
  for (const pattern of aff.BREAK) {
    for (const m of text.matchAll(pattern)) {
      const start = text.slice(0, m.index!)
      const rest = text.slice(0, m.index! + m[0].length)
      for (const breaking of breakWord(aff, rest, depth + 1)) {
        yield [start, ...breaking]
      }
    }
  }
}

export interface DeOpts {
  required: Flags
  forbidden: Flags
  nested?: boolean
  crossproduct?: boolean
}

export function isGoodAffix(affix: Affix, word: string, opts: DeOpts) {
  if (affix instanceof Suffix) {
    if (!(opts.crossproduct || affix.crossproduct)) return false
  }

  for (const flag of affix.flags) {
    if (opts.forbidden.has(flag) || !opts.required.has(flag)) return false
  }

  return affix.lookupRegex.test(word)
}

export function* desuffix(aff: Aff, word: string, opts: DeOpts): Iterable<AffixForm> {
  const segments = aff.suffixesIndex.segments(reverse(word))

  if (segments) {
    const possibleSuffixes = iterate(segments)
      .flatten()
      .filter(suffix => isGoodAffix(suffix, word, opts))

    for (const suffix of possibleSuffixes) {
      const stem = word.replace(suffix.replaceRegex, suffix.strip)

      yield new AffixForm(word, stem, { suffix })

      if (!opts.nested) {
        const iter = desuffix(aff, stem, {
          required: iterate(suffix.flags).concat(opts.required).toSet(),
          forbidden: opts.forbidden,
          nested: true,
          crossproduct: opts.crossproduct
        })

        for (const form2 of iter) {
          yield form2.replace({ text: word, suffix2: suffix })
        }
      }
    }
  }
}

export function* deprefix(aff: Aff, word: string, opts: DeOpts): Iterable<AffixForm> {
  const segments = aff.prefixesIndex.segments(word)

  if (segments) {
    const possiblePrefixes = iterate(segments)
      .flatten()
      .filter(prefix => isGoodAffix(prefix, word, opts))

    for (const prefix of possiblePrefixes) {
      const stem = word.replace(prefix.replaceRegex, prefix.strip)

      yield new AffixForm(word, stem, { prefix })

      if (!opts.nested && aff.COMPLEXPREFIXES) {
        const iter = deprefix(aff, stem, {
          required: iterate(prefix.flags).concat(opts.required).toSet(),
          forbidden: opts.forbidden,
          crossproduct: opts.crossproduct
        })

        for (const form2 of iter) {
          yield form2.replace({ text: word, prefix2: prefix })
        }
      }
    }
  }
}
