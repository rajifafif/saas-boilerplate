// @ts-nocheck
import { ref, useRoute, useRouter, watch } from 'vue';

export function useTableFilters(defaults: { itemsPerPage: number }) {
  const route = useRoute();
  const router = useRouter();

  const searchQuery = ref(route.query.q as string || '');
  const searchInput = ref(searchQuery.value);
  const selectedStatus = ref(route.query.status as string || undefined);
  const selectedCategory = ref(route.query.category as string || undefined);
  const itemsPerPage = ref(Number(route.query.itemsPerPage) || defaults.itemsPerPage);
  const page = ref(Number(route.query.page) || 1);
  const sortBy = ref(route.query.sortBy as string || undefined);
  const sortDirection = ref(route.query.sortDirection as string || undefined);

  watch(
    [searchQuery, selectedStatus, selectedCategory, itemsPerPage, page, sortBy, sortDirection],
    () => {
      const query: Record<string, string | undefined> = {
        q: searchQuery.value || undefined,
        status: selectedStatus.value || undefined,
        category: selectedCategory.value || undefined,
        page: page.value !== 1 ? String(page.value) : undefined,
        itemsPerPage: itemsPerPage.value !== defaults.itemsPerPage ? String(itemsPerPage.value) : undefined,
        sortBy: sortBy.value || undefined,
        sortDirection: sortDirection.value || undefined,
      };

      Object.keys(query).forEach((key) => {
        if (query[key] === undefined) delete query[key];
      });

      router.replace({ query });
    }
  );

  return {
    searchQuery,
    searchInput,
    selectedStatus,
    selectedCategory,
    itemsPerPage,
    page,
    sortBy,
    sortDirection,
  };
}
