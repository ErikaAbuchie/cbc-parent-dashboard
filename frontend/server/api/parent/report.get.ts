type ParentReport = {
  student: { full_name: string; admission_number: string; class_name: string; school_name: string; grade_level: number }
  term: string; finalised_at: string; has_demo_results: boolean; summary_method: string
  trend: { year: number | null; terms: string[]; subjects: { id: number; name: string;
    points: { term: string; average_score: number | null; assessed_count: number; has_demo_results: boolean }[] }[] }
  subjects: { id: number; name: string; summary_level: string | null; average_score: number | null; assessed_count: number;
    strands: { id: number; name: string; summary_level: string | null; average_score: number | null; assessed_count: number;
      sub_strands: { id: number; name: string; competency_level: string; remarks: string | null }[] }[] }[]
  assessments: { learning_area: string; strand: string; sub_strand: string; competency_level: string; remarks: string | null }[]
}

export default defineEventHandler(async (event) => {
  setResponseHeader(event, 'Cache-Control', 'no-store')
  const token = getCookie(event, 'parent_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Please verify your access code.' })
  try {
    return await $fetch<ParentReport>(`${useRuntimeConfig(event).apiBase}/parent/report`, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` }, timeout: 10000
    })
  } catch (error: any) {
    if (error.response?.status === 401) {
      deleteCookie(event, 'parent_session', { path: '/' })
      throw createError({ statusCode: 401, statusMessage: 'Your report session has ended. Please verify your code again.' })
    }
    throw createError({ statusCode: 502, statusMessage: 'Cannot load the report. Please try again.' })
  }
})
