import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Activity from 'App/Models/Activity'
import GetActivityByIdValidator from 'App/Validators/Activity/GetActivityByIdValidator'

export default class ActivityController {
  public async show({ request }: HttpContextContract) {
    const { params } = await request.validate(GetActivityByIdValidator)
    const activity = await Activity.findByOrFail('id', params.id)

    return {
      data: {
        activity,
      },
    }
  }
}
