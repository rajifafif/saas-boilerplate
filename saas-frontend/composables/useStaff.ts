import type { Staff, StaffInput } from "@/types/staff";

export const useStaff = () => {
  const staffs = ref<Staff[]>([]);
  const loadingStaffs = ref(false);

  const fetchStaffs = async (options) => {
    loadingStaffs.value = true;
    try {
      const { data, response, error } = await useApi(createUrl('/staffs', options)).json();

      staffs.value = data.value.data || [];
      
      return { data: data.value, response, error };
    } catch (error) {
      console.error("Error fetching staff:", error);
      return { data: null, response: null, error };
    } finally {
      loadingStaffs.value = false;
    }
  };

  const deleteStaff = async (id: string) => {
    return await useApi(`/staffs/${id}`, {
      method: 'DELETE',
    })
  }

  const reactivateStaff = async (id: string) => {
    return await useApi(`/staffs/${id}/reactivate`, {
      method: 'PUT',
    })
  }

  const updateStaff = async (id: string, data: StaffInput) => {
    return await useApi(`/staffs/${id}`, {
      method: 'PUT',
    })
  }

  const storeStaff = async (data: StaffInput) => {
    return await useApi(`/staffs`, {
      method: 'POST',
    })
  }

  

  return { staffs, loadingStaffs, fetchStaffs, deleteStaff, reactivateStaff, storeStaff, updateStaff };
};
