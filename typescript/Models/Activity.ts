import { DateTime } from 'luxon'
import { BaseModel, column } from '@ioc:Adonis/Lucid/Orm'

export default class Activity extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public title: string

  @column()
  public type: string

  @column()
  public status: string

  @column()
  public recipient: string

  @column()
  public userId: string

  @column()
  public isDeleted: boolean = false

  @column()
  public emailId: string

  @column()
  public subject: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime
}
