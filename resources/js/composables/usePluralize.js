import { computed } from 'vue';

export function usePluralize() {
  const pluralize = (word, count) => {
    const irregularPlurals = {
      person: 'people',
      member: 'members',
      man: 'men',
      woman: 'women',
      group: 'groups',
      goose: 'geese',
      loan: 'loans',
      contribution: 'contributions',
    };

    if (count === 1) {
      return word;
    }

    if (irregularPlurals[word]) {
      return irregularPlurals[word];
    }

    if (word.endsWith('y') && !/[aeiou]y$/.test(word)) {
      return word.replace(/y$/, 'ies');
    }

    if (word.endsWith('s') || word.endsWith('sh') || word.endsWith('ch') || word.endsWith('x') || word.endsWith('z')) {
      return word + 'es';
    }

    return word + 's';
  };

  const pluralizeWord = (count, word) => computed(() => pluralize(word, count));

  return {
    pluralizeWord,
  };
}
